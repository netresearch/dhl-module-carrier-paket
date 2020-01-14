<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Data\PackageAdditionalFactory;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\Sdk\LocationFinder\Api\Data\LocationInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterfaceFactory;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageItemInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentRequest\RecipientInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterface;
use Dhl\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterface;
use Dhl\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterfaceFactory;
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
 */
class RequestExtractor implements RequestExtractorInterface
{
    /**
     * @var RequestExtractorInterfaceFactory
     */
    private $requestExtractorFactory;

    /**
     * @var PackageAdditionalFactory
     */
    private $packageAdditionalFactory;

    /**
     * @var PackageInterfaceFactory
     */
    private $packageFactory;

    /**
     * @var Request
     */
    private $shipmentRequest;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

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
     *
     * @param RequestExtractorInterfaceFactory $requestExtractorFactory
     * @param PackageAdditionalFactory $packageAdditionalFactory
     * @param PackageInterfaceFactory $packageFactory
     * @param Request $shipmentRequest
     * @param ModuleConfig $moduleConfig
     * @param ShippingProducts $shippingProducts
     * @param Reflection $hydrator
     */
    public function __construct(
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        PackageAdditionalFactory $packageAdditionalFactory,
        PackageInterfaceFactory $packageFactory,
        Request $shipmentRequest,
        ModuleConfig $moduleConfig,
        ShippingProducts $shippingProducts,
        Reflection $hydrator
    ) {
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->packageAdditionalFactory = $packageAdditionalFactory;
        $this->packageFactory = $packageFactory;
        $this->shipmentRequest = $shipmentRequest;
        $this->moduleConfig = $moduleConfig;
        $this->shippingProducts = $shippingProducts;
        $this->hydrator = $hydrator;
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
    public function getPackageWeight(): float
    {
        return $this->getCoreExtractor()->getPackageWeight();
    }

    /**
     * Extract packages from shipment request.
     *
     * @return PackageInterface[]
     * @throws LocalizedException
     */
    public function getPackages(): array
    {
        $packages = $this->getCoreExtractor()->getPackages();
        if (count($packages) > 1) {
            throw new LocalizedException(__('Multi package shipments are not supported.'));
        }

        $paketPackages = [];
        foreach ($packages as $packageId => $package) {
            // read generic export data from shipment request
            $packageParams = $this->shipmentRequest->getData('packages')[$packageId]['params'];
            $customsParams = $packageParams['customs'] ?? [];

            // add paket-specific export data to package data
            $additionalData['additionalFee'] = (float) ($customsParams['additionalFee'] ?? '');
            $additionalData['placeOfCommittal'] = $customsParams['placeOfCommittal'] ?? '';
            $additionalData['permitNumber'] = $customsParams['permitNumber'] ?? '';
            $additionalData['attestationNumber'] = $customsParams['attestationNumber'] ?? '';
            $additionalData['electronicExportNotification'] = $customsParams['electronicExportNotification'] ?? false;

            try {
                $packageData = $this->hydrator->extract($package);
                $packageData['packageAdditional'] = $this->packageAdditionalFactory->create($additionalData);

                // create new extended package instance with paket-specific export data
                $paketPackages[$packageId] = $this->packageFactory->create($packageData);
            } catch (\Exception $exception) {
                throw new LocalizedException(__('An error occurred while preparing package data.'), $exception);
            }
        }

        return $paketPackages;
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
     * Obtain all items for the current package.
     *
     * @return PackageItemInterface[]
     */
    public function getPackageItems(): array
    {
        return $this->getCoreExtractor()->getPackageItems();
    }

    /**
     * Check if "cash on delivery" was chosen for the current shipment request.
     *
     * @return bool
     */
    public function isCashOnDelivery(): bool
    {
        return $this->getCoreExtractor()->isCashOnDelivery();
    }

    /**
     * Obtain the 14-digit billing number for the current package.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getBillingNumber(): string
    {
        $packages = $this->getPackages();

        /** @var PackageInterface $package */
        $package = current($packages);

        $storeId = $this->getCoreExtractor()->getStoreId();
        $productCode = $package->getProductCode();
        $procedure = $this->shippingProducts->getProcedure($productCode);
        $ekp = $this->moduleConfig->getEkp($storeId);

        $participations = $this->moduleConfig->getParticipations($storeId);
        $participation = $participations[$procedure] ?? '';

        return $ekp . $procedure . $participation;
    }

    /**
     * Generate DHL billing number for return shipments
     *
     * @return string
     * @throws LocalizedException
     */
    public function getReturnShipmentAccountNumber(): string
    {
        try {
            $packages = $this->getPackages();

            /** @var PackageInterface $package */
            $package = array_shift($packages);

            $storeId = $this->getCoreExtractor()->getStoreId();
            $productCode = $package->getProductCode();
            $procedure = $this->shippingProducts->getReturnProcedure($productCode);
            if ($procedure) {
                $ekp = $this->moduleConfig->getEkp($storeId);
                $participation = $this->moduleConfig->getParticipations($storeId)[$procedure] ?? '';

                return $ekp . $procedure . $participation;
            }

            return '';
        } catch (LocalizedException $exception) {
            throw new LocalizedException(__('Unable to determine return shipment billing number.'), $exception);
        }
    }

    /**
     * Obtain shipment date.
     *
     * @return \DateTime
     * @throws \RuntimeException
     */
    public function getShipmentDate(): \DateTime
    {
        return $this->getCoreExtractor()->getShipmentDate();
    }

    /**
     * Obtain the service data array.
     *
     * @param string $serviceName
     * @return string[]
     */
    private function getServiceData(string $serviceName): array
    {
        $packages = $this->shipmentRequest->getData('packages');
        $packageId = $this->shipmentRequest->getData('package_id');
        return $packages[$packageId]['params']['services'][$serviceName] ?? [];
    }

    /**
     * Check if recipient email must be set.
     *
     * By default, recipient email address is not included with the request.
     * There are some services though that require an email address.
     *
     * @return bool
     */
    public function isRecipientEmailRequired(): bool
    {
        if ($this->isParcelAnnouncement()) {
            // parcel announcement services requires email address
            return true;
        }

        $pickupLocationData = $this->getServiceData(Codes::CHECKOUT_SERVICE_PARCELSHOP_FINDER);
        if (!empty($pickupLocationData['enabled'])
            && ($pickupLocationData['locationType'] === LocationInterface::TYPE_POSTOFFICE)
            && empty($pickupLocationData['customerPostnumber'])
        ) {
            // Postfiliale delivery with no post number requires email address
            return true;
        }

        return false;
    }

    /**
     * Obtain the "bulkyGoods" flag for the current package.
     *
     * @return bool
     */
    public function isBulkyGoods(): bool
    {
        return (bool) ($this->getServiceData(Codes::PACKAGING_SERVICE_BULKY_GOODS)['enabled'] ?? false);
    }

    /**
     * Obtain the "additionalInsurance" flag for the current package.
     *
     * @return bool
     */
    public function isAdditionalInsurance(): bool
    {
        return (bool) ($this->getServiceData(Codes::PACKAGING_SERVICE_INSURANCE)['enabled'] ?? false);
    }

    /**
     * Check if preferredTime has been booked
     *
     * @return bool
     */
    public function hasPreferredTime(): bool
    {
        return (bool) ($this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_TIME)['enabled'] ?? false);
    }

    /**
     * Obtain the "preferredTime" value for the current package.
     *
     * @return string
     */
    public function getPreferredTime(): string
    {
        return $this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_TIME)['time'] ?? '';
    }

    /**
     * Check if preferredDay has been booked
     *
     * @return bool
     */
    public function hasPreferredDay(): bool
    {
        return (bool) ($this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_DAY)['enabled'] ?? false);
    }

    /**
     * Obtain the "preferredDay" value for the current package.
     *
     * @return string
     */
    public function getPreferredDay(): string
    {
        return $this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_DAY)['date'] ?? '';
    }

    /**
     * Check if preferredNeighbour has been booked
     *
     * @return bool
     */
    public function hasPreferredNeighbour(): bool
    {
        $serviceData = $this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_NEIGHBOUR);
        return (bool) ($serviceData['enabled'] ?? false);
    }

    /**
     * Obtain the name of "preferredNeighbour" value for the current package.
     *
     * @return string
     */
    public function getPreferredNeighbour(): string
    {
        $name = $this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_NEIGHBOUR)['name'] ?? '';
        $address = $this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_NEIGHBOUR)['address'] ?? '';

        return trim("$name $address");
    }

    /**
     * Check if preferredLocation has been booked
     *
     * @return bool
     */
    public function hasPreferredLocation(): bool
    {
        $serviceData = $this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_LOCATION);
        return (bool) ($serviceData['enabled'] ?? false);
    }

    /**
     * Obtain the "preferredLocation" value for the current package.
     *
     * @return string
     */
    public function getPreferredLocation(): string
    {
        return $this->getServiceData(Codes::CHECKOUT_SERVICE_PREFERRED_LOCATION)['details'] ?? '';
    }

    /**
     * Obtain the "printOnlyIfCodeable" flag for the current package.
     *
     * @return bool
     */
    public function isPrintOnlyIfCodeable(): bool
    {
        return (bool) ($this->getServiceData(Codes::PACKAGING_PRINT_ONLY_IF_CODEABLE)['enabled'] ?? false);
    }

    /**
     * Obtain the "visualCheckOfAge" flag for the current package.
     *
     * @return bool
     */
    public function isVisualCheckOfAge(): bool
    {
        return (bool) ($this->getServiceData(Codes::PACKAGING_SERVICE_CHECK_OF_AGE)['enabled'] ?? false);
    }

    /**
     * Obtain the "visualCheckOfAge" value for the current package.
     *
     * @return string
     */
    public function getVisualCheckOfAge(): string
    {
        return $this->getServiceData(Codes::PACKAGING_SERVICE_CHECK_OF_AGE)['enabled'];
    }

    /**
     * Obtain the "parcelAnnouncement" flag for the current package.
     *
     * @return bool
     */
    public function isParcelAnnouncement(): bool
    {
        return (bool) ($this->getServiceData(Codes::CHECKOUT_PARCEL_ANNOUNCEMENT)['enabled'] ?? false);
    }

    /**
     * Obtain the "returnShipment" flag for the current package.
     *
     * @return bool
     */
    public function isReturnShipment(): bool
    {
        $serviceData = $this->getServiceData(Codes::PACKAGING_SERVICE_RETURN_SHIPMENT);
        return (bool) ($serviceData['enabled'] ?? false);
    }

    /**
     * Obtain the "parcelshopFinder" flag for the current package.
     *
     * @return bool
     */
    public function isPickupLocationDelivery(): bool
    {
        $serviceData = $this->getServiceData(Codes::CHECKOUT_SERVICE_PARCELSHOP_FINDER);
        return (bool) ($serviceData['enabled'] ?? false);
    }

    /**
     * Obtain the id value of "parcelshopFinder" value for the current package.
     *
     * @return string[]
     */
    public function getPickupLocationDetails(): array
    {
        return $this->getServiceData(Codes::CHECKOUT_SERVICE_PARCELSHOP_FINDER) ?? [];
    }

    /**
     * Obtain the "parcelOutletRouting" flag for the current package.
     *
     * @return bool
     */
    public function isParcelOutletRouting(): bool
    {
        return (bool) ($this->getServiceData('parcelOutletRouting')['enabled'] ?? false);
    }

    /**
     * Obtain the "reasonForPayment" value for the current package.
     *
     * @return string[] Array of maximum two lines.
     */
    public function getCodReasonForPayment(): array
    {
        $serviceData = $this->getServiceData(Codes::PACKAGING_SERVICE_CASH_ON_DELIVERY)['reasonForPayment'] ?? '';

        // try splitting the string between words first
        $lines = explode("\n", wordwrap($serviceData, 35));
        if (count($lines) < 3) {
            return $lines;
        }

        // if that did not succeed, split hard
        return array_splice(str_split($serviceData, 35), 2);
    }
}