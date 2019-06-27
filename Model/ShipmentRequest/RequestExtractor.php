<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\PackageInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\PackageItemInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\RecipientInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\ShipperInterface;
use Dhl\ShippingCore\Api\RequestExtractorInterface;
use Dhl\ShippingCore\Api\RequestExtractorInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;

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
     * @var ShippingProductsInterface
     */
    private $shippingProducts;

    /**
     * @var RequestExtractorInterface
     */
    private $coreExtractor;

    /**
     * RequestExtractor constructor.
     * @param RequestExtractorInterfaceFactory $requestExtractorFactory
     * @param Request $shipmentRequest
     * @param ModuleConfig $moduleConfig
     * @param ShippingProductsInterface $shippingProducts
     */
    public function __construct(
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        Request $shipmentRequest,
        ModuleConfig $moduleConfig,
        ShippingProductsInterface $shippingProducts
    ) {
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->shipmentRequest = $shipmentRequest;
        $this->moduleConfig = $moduleConfig;
        $this->shippingProducts = $shippingProducts;
    }

    /**
     * Obtain core extractor for forwarding generic shipment data calls.
     *
     * @return RequestExtractorInterface
     */
    private function getCoreExtractor(): RequestExtractorInterface
    {
        if (empty($this->coreExtractor)) {
            $this->coreExtractor = $this->requestExtractorFactory->create(
                ['shipmentRequest' => $this->shipmentRequest]
            );
        }

        return $this->coreExtractor;
    }

    /**
     * @inheritdoc
     */
    public function isReturnShipmentRequest(): bool
    {
        return $this->getCoreExtractor()->isReturnShipmentRequest();
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): int
    {
        return $this->getCoreExtractor()->getStoreId();
    }

    /**
     * @inheritdoc
     */
    public function getBaseCurrencyCode(): string
    {
        return $this->getCoreExtractor()->getBaseCurrencyCode();
    }

    /**
     * @inheritdoc
     */
    public function getOrder(): Order
    {
        return $this->getCoreExtractor()->getOrder();
    }

    /**
     * @inheritdoc
     */
    public function getShipment(): Shipment
    {
        return $this->getCoreExtractor()->getShipment();
    }

    /**
     * Extract shipper from shipment request.
     *
     * @return ShipperInterface
     */
    public function getShipper(): ShipperInterface
    {
        return $this->getCoreExtractor()->getShipper();
    }

    /**
     * Extract recipient from shipment request.
     *
     * @return RecipientInterface
     */
    public function getRecipient(): RecipientInterface
    {
        return $this->getCoreExtractor()->getRecipient();
    }

    /**
     * @inheritDoc
     */
    public function getShippingMethod(): string
    {
        return $this->getCoreExtractor()->getShippingMethod();
    }

    /**
     * @inheritDoc
     */
    public function getPackageWeight(): float
    {
        return $this->getCoreExtractor()->getPackageWeight();
    }

    /**
     * @inheritDoc
     */
    public function getPackages(): array
    {
        $packages = $this->getCoreExtractor()->getPackages();
        if (count($packages) > 1) {
            throw new LocalizedException(__('Multi package shipments are not supported.'));
        }

        return $packages;
    }

    /**
     * Obtain all items from all packages.
     *
     * @return PackageItemInterface[]
     */
    public function getAllItems(): array
    {
        return $this->getCoreExtractor()->getAllItems();
    }

    /**
     * @inheritDoc
     */
    public function getPackageItems(): array
    {
        return $this->getCoreExtractor()->getPackageItems();
    }

    /**
     * @inheritDoc
     */
    public function isCashOnDelivery(): bool
    {
        return $this->getCoreExtractor()->isCashOnDelivery();
    }

    /**
     * Check if "print only if codeable" should be set for the current shipment request.
     *
     * @todo(nr): read flag from shipment request once it's available there.
     * @return bool
     */
    public function isPrintOnlyIfCodeable(): bool
    {
        $storeId = $this->getCoreExtractor()->getStoreId();
        return $this->moduleConfig->printOnlyIfCodeable($storeId);
    }

    /**
     * Obtain the 14-digit billing number for the current package.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getBillingNumber(): string
    {
        /** @var PackageInterface $package */
        $package = current($this->getPackages());

        $storeId = $this->getCoreExtractor()->getStoreId();

        //todo(nr): pull product code from proper field in shipment request once it's available there.
        $productCode = $package->getContainerType();
        $ekp = $this->moduleConfig->getEkp($storeId);
        $participations = $this->moduleConfig->getParticipations($storeId);

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

    /**
     * Returns the dangerous goods category of an item.
     *
     * @return null|string
     */
    public function getDangerousGoodsCategory()
    {
        return $this->getCoreExtractor()->getDangerousGoodsCategory();
    }
}
