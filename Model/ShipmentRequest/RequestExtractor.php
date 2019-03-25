<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\PackageInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\RecipientInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\ShipperInterface;
use Dhl\ShippingCore\Api\RequestExtractorInterface;
use Dhl\ShippingCore\Api\RequestExtractorInterfaceFactory;
use Dhl\ShippingCore\Model\Config\CoreConfigInterface;
use Dhl\ShippingCore\Util\StreetSplitterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Zend\Hydrator\Reflection;

/**
 * Class RequestExtractor
 *
 * The original shipment request is a rather limited DTO with unstructured data (DataObject, array).
 * The extractor and its subtypes offer a well-defined interface to extract the request data and
 * isolates the toxic part of extracting unstructured array data from the shipment request.
 *
 * @package Dhl\Paket\Model
 */
class RequestExtractor implements RequestExtractorInterface
{
    /**
     * @var RequestExtractorInterfaceFactory
     */
    private $requestExtractorFactory;

    /**
     * @var Request
     */
    private $shipmentRequest;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var CoreConfigInterface
     */
    private $coreConfig;

    /**
     * @var ShippingProductsInterface
     */
    private $shippingProducts;

    /**
     * @var StreetSplitterInterface
     */
    private $streetSplitter;

    /**
     * @var Reflection
     */
    private $hydrator;

    /**
     * @var RequestExtractorInterface
     */
    private $coreExtractor;

    /**
     * RequestExtractor constructor.
     * @param RequestExtractorInterfaceFactory $requestExtractorFactory
     * @param Request $shipmentRequest
     * @param ModuleConfig $moduleConfig
     * @param CoreConfigInterface $coreConfig
     * @param ShippingProductsInterface $shippingProducts
     * @param Reflection $hydrator
     * @param StreetSplitterInterface $streetSplitter
     */
    public function __construct(
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        Request $shipmentRequest,
        ModuleConfig $moduleConfig,
        CoreConfigInterface $coreConfig,
        ShippingProductsInterface $shippingProducts,
        Reflection $hydrator,
        StreetSplitterInterface $streetSplitter
    ) {
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->shipmentRequest = $shipmentRequest;
        $this->moduleConfig = $moduleConfig;
        $this->coreConfig = $coreConfig;
        $this->shippingProducts = $shippingProducts;
        $this->hydrator = $hydrator;
        $this->streetSplitter = $streetSplitter;

        $this->coreExtractor = $this->requestExtractorFactory->create(['shipmentRequest' => $shipmentRequest]);
    }

    /**
     * @inheritdoc
     */
    public function isReturnShipmentRequest(): bool
    {
        return $this->coreExtractor->isReturnShipmentRequest();
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): int
    {
        return $this->coreExtractor->getStoreId();
    }

    /**
     * @inheritdoc
     */
    public function getBaseCurrencyCode(): string
    {
        return $this->coreExtractor->getBaseCurrencyCode();
    }

    /**
     * @inheritdoc
     */
    public function getOrder(): Order
    {
        return $this->coreExtractor->getOrder();
    }

    /**
     * @inheritdoc
     */
    public function getShipment(): Shipment
    {
        return $this->coreExtractor->getShipment();
    }

    /**
     * Extract shipper from shipment request.
     *
     * @return Shipper
     * @throws \ReflectionException
     */
    public function getShipper(): ShipperInterface
    {
        $shipper = $this->coreExtractor->getShipper();
        $shipperData = $this->hydrator->extract($shipper);

        $street = (string) $this->shipmentRequest->getShipperAddressStreet();
        $streetParts = $this->streetSplitter->splitStreet($street);

        $shipperData['streetName'] = $streetParts['street_name'];
        $shipperData['streetNumber'] = $streetParts['street_number'];
        $shipperData['addressAddition'] = $streetParts['supplement'];

        /** @var Shipper $shipper */
        $shipper = (new \ReflectionClass(Shipper::class))->newInstanceWithoutConstructor();
        $this->hydrator->hydrate($shipperData, $shipper);

        return $shipper;
    }

    /**
     * Extract recipient from shipment request.
     *
     * @return Recipient
     * @throws \ReflectionException
     */
    public function getRecipient(): RecipientInterface
    {
        $recipient = $this->coreExtractor->getRecipient();
        $recipientData = $this->hydrator->extract($recipient);

        $street = (string) $this->shipmentRequest->getRecipientAddressStreet();
        $streetParts = $this->streetSplitter->splitStreet($street);

        $recipientData['streetName'] = $streetParts['street_name'];
        $recipientData['streetNumber'] = $streetParts['street_number'];
        $recipientData['addressAddition'] = $streetParts['supplement'];

        /** @var Recipient $recipient */
        $recipient = (new \ReflectionClass(Recipient::class))->newInstanceWithoutConstructor();
        $this->hydrator->hydrate($recipientData, $recipient);

        return $recipient;
    }

    /**
     * @inheritdoc
     */
    public function getShippingMethod(): string
    {
        return $this->coreExtractor->getShippingMethod();
    }

    /**
     * @inheritdoc
     */
    public function getPackageWeight(): float
    {
        return $this->coreExtractor->getPackageWeight();
    }

    /**
     * @inheritdoc
     */
    public function getAllPackages(): array
    {
        return $this->coreExtractor->getAllPackages();
    }

    /**
     * @inheritdoc
     */
    public function getPackage(): PackageInterface
    {
        return $this->coreExtractor->getPackage();
    }

    /**
     * @inheritdoc
     */
    public function getAllItems(): array
    {
        return $this->coreExtractor->getAllItems();
    }

    /**
     * @inheritdoc
     */
    public function getPackageItems(): array
    {
        return $this->coreExtractor->getPackageItems();
    }

    /**
     * Check if "print only if codeable" should be set for the current shipment request.
     *
     * @todo(nr): read flag from shipment request once it's available there.
     * @return bool
     */
    public function isPrintOnlyIfCodeable(): bool
    {
        $storeId = $this->coreExtractor->getStoreId();
        return $this->moduleConfig->printOnlyIfCodeable($storeId);
    }

    /**
     * Check if "cash on delivery" was chosen for the current shipment request.
     *
     * @return bool
     */
    public function isCashOnDelivery(): bool
    {
        $storeId = $this->coreExtractor->getStoreId();
        $order = $this->coreExtractor->getOrder();

        return $this->coreConfig->isCodPaymentMethod($order->getPayment()->getMethod(), $storeId);
    }

    /**
     * Obtain the 14-digit billing number for the current package.
     *
     * @todo(nr): pull product code from proper field in shipment request once it's available there.
     * @return string
     * @throws LocalizedException
     */
    public function getBillingNumber(): string
    {
        $storeId = $this->coreExtractor->getStoreId();

        $productCode = $this->coreExtractor->getPackage()->getContainerType();
        $ekp = $this->moduleConfig->getAccountNumber($storeId);
        $participations = $this->moduleConfig->getAccountParticipations($storeId);

        return $this->shippingProducts->getBillingNumber($productCode, $ekp, $participations);
    }

    /**
     * Obtain shipment date.
     *
     * @todo(nr): take into account cut-off settings etc.
     * @return string
     */
    public function getShipmentDate(): string
    {
        return date('Y-m-d');
    }
}
