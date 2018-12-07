<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\ShippingCore\Api\RateRequestEmulationInterface;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;

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
     * @var ResultFactory
     */
    private $rateResultFactory;

    /**
     * Paket constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param RateRequestEmulationInterface $rateRequestEmulation
     * @param ResultFactory $resultFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        RateRequestEmulationInterface $rateRequestEmulation,
        ResultFactory $resultFactory,
        array $data = []
    ) {
        $this->rateRequestService = $rateRequestEmulation;
        $this->rateResultFactory = $resultFactory;

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
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

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigData('active')) {
            return false;
        }

        $emulatedCarrier = $this->getConfigData('emulated_carrier');
        $result = $this->rateResultFactory->create();
        /** @var Result $rateResult */
        $rateResult = $this->rateRequestService->emulateRateRequest($emulatedCarrier, $request);
        if ($rateResult instanceof Result) {
            $rates = $rateResult->getAllRates();
            $rates = array_map(
                function ($rate) {
                    /** @var Method $rate */
                    $rate->setData('carrier', $this->_code);
                    $rate->setData('carrier_title', $this->getConfigData('title'));

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
     * Get allowed shipping methods
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
        return [];
    }

    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        throw new \RuntimeException('Not yet implemented');
    }
}
