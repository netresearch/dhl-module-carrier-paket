<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\ShippingCore\Api\Data\TrackResponse\TrackErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Tracking\Result as TrackingResult;

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
     * @var \Dhl\ShippingCore\Api\RateRequestEmulationInterface
     */
    private $rateRequestService;

    /**
     * @var \Dhl\Paket\Model\ShipmentManagement
     */
    private $shipmentManagement;

    /**
     * @var \Dhl\Paket\Model\Config\ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var \Dhl\ShippingCore\Api\ConfigInterface
     */
    private $dhlConfig;

    /**
     * @var \Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface
     */
    private $shippingProducts;

    /**
     * @var \Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterfaceFactory
     */
    private $trackRequestFactory;

    /**
     * @var \Dhl\ShippingCore\Model\Emulation\ProxyCarrierFactory
     */
    private $proxyCarrierFactory;

    /**
     * @var \Magento\Shipping\Model\Carrier\AbstractCarrierInterface
     */
    private $proxyCarrier;

    /**
     * Paket carrier constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Xml\Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Dhl\ShippingCore\Api\RateRequestEmulationInterface $rateRequestService
     * @param \Dhl\Paket\Model\ShipmentManagement $shipmentManagement
     * @param \Dhl\Paket\Model\Config\ModuleConfig $moduleConfig
     * @param \Dhl\ShippingCore\Api\ConfigInterface $dhlConfig
     * @param \Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface $shippingProducts
     * @param \Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterfaceFactory $trackRequestFactory
     * @param \Dhl\ShippingCore\Model\Emulation\ProxyCarrierFactory $proxyCarrierFactory
     * @param mixed[] $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Xml\Security $xmlSecurity,
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
        \Dhl\ShippingCore\Api\RateRequestEmulationInterface $rateRequestService,
        \Dhl\Paket\Model\ShipmentManagement $shipmentManagement,
        \Dhl\Paket\Model\Config\ModuleConfig $moduleConfig,
        \Dhl\ShippingCore\Api\ConfigInterface $dhlConfig,
        \Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface $shippingProducts,
        \Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterfaceFactory $trackRequestFactory,
        \Dhl\ShippingCore\Model\Emulation\ProxyCarrierFactory $proxyCarrierFactory,
        array $data = []
    ) {
        $this->rateRequestService = $rateRequestService;
        $this->shipmentManagement = $shipmentManagement;
        $this->moduleConfig = $moduleConfig;
        $this->dhlConfig = $dhlConfig;
        $this->shippingProducts = $shippingProducts;
        $this->trackRequestFactory = $trackRequestFactory;
        $this->proxyCarrierFactory = $proxyCarrierFactory;

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
     * DHL Paket carrier only ships from DE or AT.
     *
     * @param DataObject $request
     * @return bool|DataObject|AbstractCarrierOnline
     */
    public function processAdditionalValidation(DataObject $request)
    {
        $originCountry = $this->dhlConfig->getOriginCountry();
        $destCodes = \Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface::ORIGIN_DEST_CODES;
        if (!\array_key_exists($originCountry, $destCodes)) {
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

        $activeFlag = $this->getData('active_flag');
        if ($activeFlag && !$this->getConfigFlag($activeFlag)) {
            return $result;
        }

        $storeId = $this->getData('store');
        $carrierCode = $this->moduleConfig->getProxyCarrierCode($storeId);

        try {
            $proxyResult = $this->rateRequestService->emulateRateRequest($carrierCode, $request);

            foreach ($proxyResult->getAllRates() as $rate) {
                // override carrier details
                $rate->setData('carrier', $this->getCarrierCode());
                $rate->setData('carrier_title', $this->getConfigData('title'));

                // check if cart price rule was applied
                if ($request->getFreeShipping()) {
                    $rate->setPrice(0.0);
                }

                $result->append($rate);
            }
        } catch (\Exception $exception) {
            $error = $this->_rateErrorFactory->create(['data' => [
                'carrier' => $this->_code,
                'carrier_title' => $this->getConfigData('title'),
                'error_message' => $this->getConfigData('specificerrmsg'),
            ]]);
            $result->append($error);

            if ($exception instanceof LocalizedException) {
                $logMessage = $exception->getLogMessage();
            } else {
                $logMessage = $exception->getMessage();
            }

            $this->_logger->error($logMessage, ['exception' => $exception]);
        }

        return $result;
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
     * Returns container types of carrier.
     *
     * @fixme(nr): DHL Paket carrier has no pre-defined containers. Package dimensions are optional. Support DHLGW-86?
     *
     * @param DataObject|null $params
     * @return string[]
     */
    public function getContainerTypes(DataObject $params = null): array
    {
        $containerTypes = parent::getContainerTypes($params);
        $countryShipper = null;
        $countryRecipient = null;

        if ($params !== null) {
            $countryShipper = $params->getData('country_shipper');
            $countryRecipient = $params->getData('country_recipient');
        }

        return array_merge(
            $containerTypes,
            $this->getShippingProducts($countryShipper, $countryRecipient)
        );
    }

    /**
     * Obtain the shipping products that match the given route.
     *
     * List might get lengthy, so we move the product that was configured as default to the top.
     *
     * @fixme(nr): Move somewhere else, only needed for product selection in packaging popup.
     * @param string $countryShipper The shipper country code
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
            $euCountries = $this->dhlConfig->getEuCountries();
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
     * Perform a shipment request to the DHL Paket web service.
     *
     * Return either tracking number and label data or a shipment error.
     * Note that Magento triggers one web service request per package in multi-package shipments.
     *
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::returnOfShipment
     *
     * @param DataObject|\Magento\Shipping\Model\Shipment\Request $request
     * @return \Magento\Framework\DataObject
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
            $cancelRequests[$trackNumber]= $this->trackRequestFactory->create([
                'storeId' => $this->getData('store'),
                'trackNumber' => $trackNumber,
            ]);
        }

        $result = $this->shipmentManagement->cancelLabels($cancelRequests);
        $errors = array_filter($result, function (TrackResponseInterface $trackResponse) {
            return ($trackResponse instanceof TrackErrorResponseInterface);
        });

        return (empty($errors) && parent::rollBack($data));
    }

    /**
     * Returns tracking information.
     *
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::getTrackingInfo
     * @param string $shipmentNumber
     * @return TrackingResult
     */
    public function getTracking(string $shipmentNumber): TrackingResult
    {
        $result = $this->_trackFactory->create();

        $statusData = [
            'tracking' => $shipmentNumber,
            'carrier_title' => $this->moduleConfig->getTitle(),
            'url' => sprintf(self::TRACKING_URL_TEMPLATE, $shipmentNumber),
        ];
        $status = $this->_trackStatusFactory->create(['data' => $statusData]);
        $result->append($status);

        return $result;
    }

    /**
     * Returns the configured proxied carrier instance.
     *
     * @return AbstractCarrierInterface
     * @throws \Magento\Framework\Exception\NotFoundException
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
}
