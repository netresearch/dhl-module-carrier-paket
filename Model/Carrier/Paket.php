<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;

/**
 * DHL Paket online shipping carrier model.
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
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \Dhl\ShippingCore\Api\RateRequestEmulationInterface
     */
    private $rateRequestService;

    /**
     * @var \Dhl\Paket\Model\Carrier\ApiGatewayFactory
     */
    private $apiGatewayFactory;

    /**
     * @var \Dhl\Paket\Model\Config\ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var \Dhl\ShippingCore\Model\Config\CoreConfigInterface
     */
    private $shippingCoreConfig;

    /**
     * @var \Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface
     */
    private $shippingProducts;

    /**
     * @var \Dhl\ShippingCore\Model\Emulation\ProxyCarrierFactory
     */
    private $proxyCarrierFactory;

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
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Dhl\ShippingCore\Api\RateRequestEmulationInterface $rateRequestService
     * @param \Dhl\Paket\Model\Carrier\ApiGatewayFactory $apiGatewayFactory
     * @param \Dhl\Paket\Model\Config\ModuleConfig $moduleConfig
     * @param \Dhl\ShippingCore\Model\Config\CoreConfigInterface $shippingCoreConfig
     * @param \Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface $shippingProducts
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
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Dhl\ShippingCore\Api\RateRequestEmulationInterface $rateRequestService,
        \Dhl\Paket\Model\Carrier\ApiGatewayFactory $apiGatewayFactory,
        \Dhl\Paket\Model\Config\ModuleConfig $moduleConfig,
        \Dhl\ShippingCore\Model\Config\CoreConfigInterface $shippingCoreConfig,
        \Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface $shippingProducts,
        \Dhl\ShippingCore\Model\Emulation\ProxyCarrierFactory $proxyCarrierFactory,
        array $data = []
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->rateRequestService = $rateRequestService;
        $this->apiGatewayFactory = $apiGatewayFactory;
        $this->moduleConfig = $moduleConfig;
        $this->shippingCoreConfig = $shippingCoreConfig;
        $this->shippingProducts = $shippingProducts;
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
        $originCountry = $this->shippingCoreConfig->getOriginCountry();
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

        /** @var Result $rateResult */
        $rateResult = $this->rateRequestService->emulateRateRequest($carrierCode, $request);
        if (!$rateResult instanceof Result) {
            return $result;
        }

        foreach ($rateResult->getAllRates() as $rate) {
            $rate->setData('carrier', $this->getCarrierCode());
            $rate->setData('carrier_title', $this->getConfigData('title'));

            // Check if cart price rule was applied
            if ($request->getFreeShipping()) {
                $rate->setPrice(0.0);
            }

            $result->append($rate);
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
        $storeId = $this->getData('store');

        $carrierCode = $this->moduleConfig->getProxyCarrierCode($storeId);
        $carrier = $this->proxyCarrierFactory->create($carrierCode);
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
            $euCountries = $this->shippingCoreConfig->getEuCountries();
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
        $api = $this->apiGatewayFactory->create([
            'logger' => $this->_logger,
            'storeId' => (int) $this->getData('store'),
        ]);

        $apiResult = $api->createShipments([$request]);

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
    public function rollBack($data)
    {
        $shipmentNumbers = array_map(function (array $info) {
            return $info['tracking_number'];
        }, $data);

        $api = $this->apiGatewayFactory->create([
            'logger' => $this->_logger,
            'storeId' => (int) $this->getData('store'),
        ]);

        $apiResult = $api->cancelShipments($shipmentNumbers);

        // if the diff between request and result is empty, then all shipments were successfully cancelled.
        $diff = array_diff($shipmentNumbers, $apiResult);

        return (empty($diff) && parent::rollBack($data));
    }

    /**
     * Check if carrier has shipping tracking option available.
     *
     * @return boolean
     */
    public function isTrackingAvailable(): bool
    {
        return true;
    }

    /**
     * Returns tracking information.
     *
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::getTrackingInfo
     * @param string $shipmentNumber
     * @return \Magento\Shipping\Model\Tracking\Result
     */
    public function getTracking(string $shipmentNumber): \Magento\Shipping\Model\Tracking\Result
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
}
