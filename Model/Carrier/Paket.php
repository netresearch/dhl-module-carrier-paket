<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\Shipment\ShipmentLabelProvider;
use Dhl\Paket\Model\Tracking\TrackingInfoProvider;
use Dhl\Sdk\Bcs\Api\ShippingProductsInterface;
use Dhl\Sdk\Bcs\Model\ShippingProducts;
use Dhl\ShippingCore\Api\RateRequestEmulationInterface;
use Dhl\ShippingCore\Model\Config\CoreConfigInterface;
use Dhl\ShippingCore\Model\Emulation\ProxyCarrierFactory;
use InvalidArgumentException;
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
use Magento\Shipping\Model\Tracking\Result as TrackingResult;
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
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var RateRequestEmulationInterface
     */
    private $rateRequestService;

    /**
     * @var ModuleConfig
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
     * @var TrackingInfoProvider
     */
    private $trackingProvider;

    /**
     * @var ShippingProductsInterface
     */
    private $shippingProducts;

    /**
     * @var ProxyCarrierFactory
     */
    private $proxyCarrierFactory;

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
     * @param ModuleConfig                  $moduleConfig
     * @param CoreConfigInterface           $shippingCoreConfig
     * @param ShipmentLabelProvider         $shipmentProvider
     * @param TrackingInfoProvider          $trackingInfoProvider
     * @param ShippingProductsInterface     $shippingProducts
     * @param ProxyCarrierFactory           $proxyCarrierFactory
     * @param array $data
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
        ModuleConfig $moduleConfig,
        CoreConfigInterface $shippingCoreConfig,
        ShipmentLabelProvider $shipmentProvider,
        TrackingInfoProvider $trackingInfoProvider,
        ShippingProductsInterface $shippingProducts,
        ProxyCarrierFactory $proxyCarrierFactory,
        array $data = []
    ) {
        $this->rateRequestService  = $rateRequestEmulation;
        $this->moduleConfig        = $moduleConfig;
        $this->shippingCoreConfig  = $shippingCoreConfig;
        $this->shipmentProvider    = $shipmentProvider;
        $this->trackingProvider    = $trackingInfoProvider;
        $this->shippingProducts    = $shippingProducts;
        $this->proxyCarrierFactory = $proxyCarrierFactory;

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
        $result          = $this->_rateFactory->create();

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

        /** @var Method $rate */
        foreach ($result->getAllRates() as $rate) {
            // Check if cart price rule was applied
            if ($request->getFreeShipping()) {
                $rate->setPrice(0.0);
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function processAdditionalValidation(DataObject $request)
    {
        $result        = parent::processAdditionalValidation($request);
        $originCountry = $this->shippingCoreConfig->getOriginCountry();

        if (!\array_key_exists($originCountry, ShippingProducts::ORIGIN_DEST_CODES)) {
            return false;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedMethods(): array
    {
        $storeId     = $this->getStore();
        $carrierCode = $this->moduleConfig->getEmulatedCarrier($storeId);
        $carrier     = $this->proxyCarrierFactory->create($carrierCode);

        return \is_callable([$carrier, 'getAllowedMethods'])
            ? $carrier->getAllowedMethods()
            : [];
    }

    /**
     * Returns container types of carrier.
     *
     * @param DataObject|null $params
     *
     * @return string[]
     */
    public function getContainerTypes(DataObject $params = null): array
    {
        $containerTypes   = parent::getContainerTypes($params);
        $countryShipper   = null;
        $countryRecipient = null;

        if ($params !== null) {
            $countryShipper   = $params->getData('country_shipper');
            $countryRecipient = $params->getData('country_recipient');
        }

        return array_merge(
            $containerTypes,
            $this->getShippingProducts($countryShipper, $countryRecipient)
        );
    }

    /**
     * Obtain the shipping products that match the given route. List might get
     * lengthy, so we move the product that was configured as default to the top.
     *
     * @param string $countryShipper   The shipper country code
     * @param string $countryRecipient The recipient country code
     *
     * @return string[]
     */
    private function getShippingProducts(
        string $countryShipper = null,
        string $countryRecipient = null
    ): array {
        // Read available codes
        if (!$countryShipper || !$countryRecipient) {
            $codes = $this->shippingProducts->getAllCodes();
        } else {
            $euCountries = $this->moduleConfig->getEuCountryList();
            $codes = $this->shippingProducts->getApplicableCodes($countryShipper, $countryRecipient, $euCountries);
        }

        // Obtain human readable names, combine to array
        $names = array_map(
            function (string $code) {
                return $this->shippingProducts->getProductName($code);
            },
            $codes
        );

        return array_combine($codes, $names);
    }

    /**
     * @inheritDoc
     */
    protected function _doShipmentRequest(DataObject $request): DataObject
    {
        if ($request instanceof Request) {
            return $this->shipmentProvider->getShipmentLabel($request);
        }

        throw new InvalidArgumentException('Shipment returns are not supported');
    }

    /**
     * Check if carrier has shipping tracking option available
     * All \Magento\Usa carriers have shipping tracking option available
     *
     * @return boolean
     */
    public function isTrackingAvailable(): bool
    {
        return false;
    }

    /**
     * Returns tracking information.
     *
     * @param string $tracking
     *
     * @return TrackingResult
     */
    public function getTracking(string $tracking): TrackingResult
    {
        return $this->trackingProvider->getTrackingInfo($tracking);
    }
}
