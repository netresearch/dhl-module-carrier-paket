<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\RatesManagement;
use Dhl\Paket\Model\ShipmentManagement;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterfaceFactory;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Model\Rate\Emulation\ProxyCarrierFactory;
use Dhl\UnifiedTracking\Api\TrackingInfoProviderInterface;
use Dhl\UnifiedTracking\Exception\TrackingException;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory as RateErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory as RateResultFactory;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\ErrorFactory as TrackErrorFactory;
use Magento\Shipping\Model\Tracking\Result\Status;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory as TrackResultFactory;
use Psr\Log\LoggerInterface;

/**
 * DHL Paket online shipping carrier model.
 *
 * @package Dhl\Paket\Model
 */
class Paket extends AbstractCarrierOnline implements CarrierInterface
{
    const CARRIER_CODE = 'dhlpaket';

    const TRACKING_URL_TEMPLATE = 'https://nolp.dhl.de/nextt-online-public/set_identcodes.do?lang=de&idc=%s';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var  RatesManagement
     */
    private $ratesManagement;

    /**
     * @var ShipmentManagement
     */
    private $shipmentManagement;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

    /**
     * @var TrackRequestInterfaceFactory
     */
    private $trackRequestFactory;

    /**
     * @var ProxyCarrierFactory
     */
    private $proxyCarrierFactory;

    /**
     * @var AbstractCarrierInterface
     */
    private $proxyCarrier;

    /**
     * @var TrackingInfoProviderInterface
     */
    private $trackingInfoProvider;

    /**
     * Paket carrier constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param RateErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param ElementFactory $xmlElFactory
     * @param RateResultFactory $rateFactory
     * @param MethodFactory $rateMethodFactory
     * @param TrackResultFactory $trackFactory
     * @param TrackErrorFactory $trackErrorFactory
     * @param StatusFactory $trackStatusFactory
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param CurrencyFactory $currencyFactory
     * @param Data $directoryData
     * @param StockRegistryInterface $stockRegistry
     * @param RatesManagement $ratesManagement
     * @param ShipmentManagement $shipmentManagement
     * @param ModuleConfig $moduleConfig
     * @param ShippingProducts $shippingProducts
     * @param TrackRequestInterfaceFactory $trackRequestFactory
     * @param ProxyCarrierFactory $proxyCarrierFactory
     * @param TrackingInfoProviderInterface $trackingInfoProvider
     * @param mixed[] $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RateErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        Security $xmlSecurity,
        ElementFactory $xmlElFactory,
        RateResultFactory $rateFactory,
        MethodFactory $rateMethodFactory,
        TrackResultFactory $trackFactory,
        TrackErrorFactory $trackErrorFactory,
        StatusFactory $trackStatusFactory,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        CurrencyFactory $currencyFactory,
        Data $directoryData,
        StockRegistryInterface $stockRegistry,
        RatesManagement $ratesManagement,
        ShipmentManagement $shipmentManagement,
        ModuleConfig $moduleConfig,
        ShippingProducts $shippingProducts,
        TrackRequestInterfaceFactory $trackRequestFactory,
        ProxyCarrierFactory $proxyCarrierFactory,
        TrackingInfoProviderInterface $trackingInfoProvider,
        array $data = []
    ) {
        $this->ratesManagement = $ratesManagement;
        $this->shipmentManagement = $shipmentManagement;
        $this->moduleConfig = $moduleConfig;
        $this->shippingProducts = $shippingProducts;
        $this->trackRequestFactory = $trackRequestFactory;
        $this->proxyCarrierFactory = $proxyCarrierFactory;
        $this->trackingInfoProvider = $trackingInfoProvider;

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

    /**
     * Check if the carrier can handle the given rate request.
     *
     * DHL Paket carrier only ships from DE.
     *
     * @param DataObject $request
     * @return bool|DataObject|AbstractCarrierOnline
     */
    public function processAdditionalValidation(DataObject $request)
    {
        $shippingOrigin = (string) $request->getData('country_id');
        $applicableProducts = $this->shippingProducts->getApplicableProducts($shippingOrigin);
        if (empty($applicableProducts)) {
            return false;
        }

        return parent::processAdditionalValidation($request);
    }

    /**
     * @inheritDoc
     */
    public function collectRates(RateRequest $request)
    {
        $result = $this->_rateFactory->create();

        if ($this->_activeFlag && !$this->getConfigFlag($this->_activeFlag)) {
            return $result;
        }
        // set carrier details for rate post-processing
        $request->setData('carrier_code', $this->getCarrierCode());
        $request->setData('carrier_title', $this->getConfigData('title'));

        $proxyResult = $this->ratesManagement->collectRates($request);
        if (!$proxyResult) {
            $result->append($this->getErrorMessage());

            return $result;
        }

        return $proxyResult;
    }

    /**
     * Obtain shipping methods offered by the carrier.
     *
     * The DHL Paket carrier does not offer own methods. The call gets
     * forwarded to another carrier as configured via module settings.
     *
     * @return string[] Associative array of method names with method code as key.
     */
    public function getAllowedMethods(): array
    {
        try {
            $carrier = $this->getProxyCarrier();
        } catch (LocalizedException $exception) {
            return [];
        }

        if (!$carrier instanceof CarrierInterface) {
            return [];
        }

        return $carrier->getAllowedMethods();
    }

    /**
     * Perform a shipment request to the DHL Paket web service.
     *
     * Return either tracking number and label data or a shipment error.
     * Note that Magento triggers one web service request per package in multi-package shipments.
     *
     * @param DataObject $request
     * @return DataObject
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::returnOfShipment
     */
    protected function _doShipmentRequest(DataObject $request): DataObject
    {
        $apiResult = $this->shipmentManagement->createLabels([$request->getData('package_id') => $request]);

        // one request, one response.
        return $apiResult[0];
    }

    /**
     * Delete requested shipments if the current shipment request is failed
     *
     * In case one request succeeded and another request failed, Magento will
     * discard the successfully created label. That means, labels created through
     * BCS API must be cancelled.
     *
     * @param string[][] $data Arrays of info data with tracking_number and label_content
     * @return bool
     */
    public function rollBack($data): bool
    {
        if (!is_array($data) || empty($data)) {
            return parent::rollBack($data);
        }

        $cancelRequests = [];
        foreach ($data as $rollbackInfo) {
            $trackNumber = $rollbackInfo['tracking_number'];
            $cancelRequests[$trackNumber] = $this->trackRequestFactory->create(
                [
                    'storeId' => $this->getData('store'),
                    'trackNumber' => $trackNumber,
                ]
            );
        }

        $result = $this->shipmentManagement->cancelLabels($cancelRequests);
        $errors = array_filter(
            $result,
            function (TrackResponseInterface $trackResponse) {
                return ($trackResponse instanceof TrackErrorResponseInterface);
            }
        );

        return (empty($errors) && parent::rollBack($data));
    }

    /**
     * Returns the configured proxied carrier instance.
     *
     * @return AbstractCarrierInterface
     * @throws NotFoundException
     */
    private function getProxyCarrier()
    {
        if (!$this->proxyCarrier) {
            $storeId = $this->getData('store');
            $carrierCode = $this->moduleConfig->getProxyCarrierCode($storeId);

            $this->proxyCarrier = $this->proxyCarrierFactory->create($carrierCode);
        }

        return $this->proxyCarrier;
    }

    /**
     * Check if city option required.
     *
     * @return boolean
     */
    public function isCityRequired(): bool
    {
        try {
            return $this->getProxyCarrier()->isCityRequired();
        } catch (LocalizedException $exception) {
            return parent::isCityRequired();
        }
    }

    /**
     * Determine whether zip-code is required for the country of destination.
     *
     * @param string|null $countryId
     * @return bool
     */
    public function isZipCodeRequired($countryId = null): bool
    {
        try {
            return $this->getProxyCarrier()->isZipCodeRequired($countryId);
        } catch (LocalizedException $exception) {
            return parent::isZipCodeRequired($countryId);
        }
    }

    /**
     * Get tracking information
     *
     * @param string $tracking
     *
     * @return string|false
     */
    public function getTrackingInfo($tracking)
    {
        try {
            $result = $this->trackingInfoProvider->getTrackingDetails($tracking, $this->getCarrierCode());
        } catch (TrackingException $exception) {
            $result = null;
        }

        if ($result instanceof Status) {
            $result->setData('carrier_title', $this->getConfigData('title'));
        } else {
            // create link to portal if web service returned an error
            $statusData = [
                'tracking' => $tracking,
                'carrier_title' => $this->getConfigData('title'),
                'url' => sprintf(self::TRACKING_URL_TEMPLATE, $tracking),
            ];

            $result = $this->_trackStatusFactory->create(['data' => $statusData]);
        }

        return $result;
    }
}
