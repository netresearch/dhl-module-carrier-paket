<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\Paket\Model\Config\ModuleConfigInterface;
use Dhl\Paket\Model\Shipment\ShipmentLabelProvider;
use Dhl\ShippingCore\Api\RateRequestEmulationInterface;
use Dhl\ShippingCore\Model\Config\CoreConfigInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory as RateResultErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory as RateResultFactory;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\ErrorFactory as TrackingErrorFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory as TrackingResultFactory;
use Psr\Log\LoggerInterface;

/**
 * DHL Paket online shipping carrier model.
 */
class Paket extends AbstractCarrierOnline implements CarrierInterface
{
    const CARRIER_CODE = 'dhlpaket';

    /**
     * List of allowed origin country codes.
     */
    const ALLOWED_ORIGIN_COUNTRIES = ['DE', 'AT'];

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var RateRequestEmulationInterface
     */
    private $rateRequestService;

    /**
     * @var RateResultFactory
     */
    private $rateResultFactory;

    /**
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * @var CoreConfigInterface
     */
    private $shippingCoreConfig;

    /**
     * @var ShipmentLabelProvider
     */
    private $shipmentProvider;

    /**
     * Paket constructor.
     *
     * @param ScopeConfigInterface          $scopeConfig
     * @param RateResultErrorFactory        $rateErrorFactory
     * @param LoggerInterface               $logger
     * @param Security                      $xmlSecurity
     * @param ElementFactory                $xmlElFactory
     * @param RateResultFactory             $rateResultFactory
     * @param MethodFactory                 $rateMethodFactory
     * @param TrackingResultFactory         $trackFactory
     * @param TrackingErrorFactory          $trackErrorFactory
     * @param StatusFactory                 $trackStatusFactory
     * @param RegionFactory                 $regionFactory
     * @param CountryFactory                $countryFactory
     * @param CurrencyFactory               $currencyFactory
     * @param Data                          $directoryData
     * @param StockRegistryInterface        $stockRegistry
     * @param RateRequestEmulationInterface $rateRequestEmulation
     * @param ModuleConfigInterface         $moduleConfig
     * @param CoreConfigInterface           $shippingCoreConfig
     * @param ShipmentLabelProvider         $shipmentProvider
     * @param array                         $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RateResultErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        Security $xmlSecurity,
        ElementFactory $xmlElFactory,
        RateResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        TrackingResultFactory $trackFactory,
        TrackingErrorFactory $trackErrorFactory,
        StatusFactory $trackStatusFactory,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        CurrencyFactory $currencyFactory,
        Data $directoryData,
        StockRegistryInterface $stockRegistry,
        RateRequestEmulationInterface $rateRequestEmulation,
        ModuleConfigInterface $moduleConfig,
        CoreConfigInterface $shippingCoreConfig,
        ShipmentLabelProvider $shipmentProvider,
        array $data = []
    ) {
        $this->rateRequestService = $rateRequestEmulation;
        $this->rateResultFactory  = $rateResultFactory;
        $this->moduleConfig       = $moduleConfig;
        $this->shippingCoreConfig = $shippingCoreConfig;
        $this->shipmentProvider   = $shipmentProvider;

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateResultFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function collectRates(RateRequest $request)
    {
        $storeId = $this->getStore();

        if (!$this->moduleConfig->isEnabled($storeId)) {
            return false;
        }

        $emulatedCarrier = $this->moduleConfig->getEmulatedCarrier($storeId);
        $result          = $this->rateResultFactory->create();

        /** @var Result $rateResult */
        $rateResult = $this->rateRequestService->emulateRateRequest($emulatedCarrier, $request);
        if ($rateResult instanceof Result) {
            $rates = $rateResult->getAllRates();
            $rates = array_map(
                function (Method $rate) use ($storeId) {
                    $rate->setCarrier($this->getCarrierCode());
                    $rate->setCarrierTitle($this->moduleConfig->getTitle($storeId));

                    return $rate;
                },
                $rates
            );

            foreach ($rates as $rate) {
                $result->append($rate);
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function proccessAdditionalValidation(DataObject $request)
    {
        $result        = parent::proccessAdditionalValidation($request);
        $originCountry = $this->shippingCoreConfig->getOriginCountry();

        if (!\in_array($originCountry, self::ALLOWED_ORIGIN_COUNTRIES, true)) {
            return false;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedMethods(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function _doShipmentRequest(DataObject $request): DataObject
    {
        if ($request instanceof Request) {
            return $this->shipmentProvider->getShipmentLabel($request);
        }

        throw new \InvalidArgumentException('Shipment returns are not supported');
    }
}
