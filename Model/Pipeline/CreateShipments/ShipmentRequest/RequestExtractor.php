<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Data\PackageAdditionalFactory;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Model\Util\ShippingProducts;
use Dhl\Sdk\UnifiedLocationFinder\Api\Data\LocationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\RecipientInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterfaceFactory;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractor\ServiceOptionReaderInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractor\ServiceOptionReaderInterfaceFactory;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterfaceFactory;

/**
 * Class RequestExtractor
 *
 * The original shipment request is a rather limited DTO with unstructured data (DataObject, array).
 * The extractor and its subtypes offer a well-defined interface to extract the request data and
 * isolates the toxic part of extracting unstructured array data from the shipment request.
 */
class RequestExtractor implements RequestExtractorInterface
{
    /**
     * @var Request
     */
    private $shipmentRequest;

    /**
     * @var RequestExtractorInterfaceFactory
     */
    private $requestExtractorFactory;

    /**
     * @var ServiceOptionReaderInterfaceFactory
     */
    private $serviceOptionReaderFactory;

    /**
     * @var ShipperInterfaceFactory
     */
    private $shipperFactory;

    /**
     * @var PackageAdditionalFactory
     */
    private $packageAdditionalFactory;

    /**
     * @var PackageInterfaceFactory
     */
    private $packageFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

    /**
     * @var RequestExtractorInterface
     */
    private $coreExtractor;

    /**
     * @var ServiceOptionReaderInterface
     */
    private $serviceOptionReader;

    /**
     * @var ShipperInterface
     */
    private $returnRecipient;

    public function __construct(
        Request $shipmentRequest,
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        ServiceOptionReaderInterfaceFactory $serviceOptionReaderFactory,
        ShipperInterfaceFactory $shipperFactory,
        PackageAdditionalFactory $packageAdditionalFactory,
        PackageInterfaceFactory $packageFactory,
        ModuleConfig $moduleConfig,
        ShippingProducts $shippingProducts
    ) {
        $this->shipmentRequest = $shipmentRequest;
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->serviceOptionReaderFactory = $serviceOptionReaderFactory;
        $this->shipperFactory = $shipperFactory;
        $this->packageAdditionalFactory = $packageAdditionalFactory;
        $this->packageFactory = $packageFactory;
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
     * Obtain service option reader to read carrier specific service data.
     *
     * @return ServiceOptionReaderInterface
     */
    private function getServiceOptionReader(): ServiceOptionReaderInterface
    {
        if (empty($this->serviceOptionReader)) {
            $this->serviceOptionReader = $this->serviceOptionReaderFactory->create(
                ['shipmentRequest' => $this->shipmentRequest]
            );
        }

        return $this->serviceOptionReader;
    }

    public function isReturnShipmentRequest(): bool
    {
        return $this->getCoreExtractor()->isReturnShipmentRequest();
    }

    public function getStoreId(): int
    {
        return $this->getCoreExtractor()->getStoreId();
    }

    public function getBaseCurrencyCode(): string
    {
        return $this->getCoreExtractor()->getBaseCurrencyCode();
    }

    public function getOrder(): Order
    {
        return $this->getCoreExtractor()->getOrder();
    }

    public function getShipment(): Shipment
    {
        return $this->getCoreExtractor()->getShipment();
    }

    public function getShipper(): ShipperInterface
    {
        return $this->getCoreExtractor()->getShipper();
    }

    public function getReturnRecipient(): ShipperInterface
    {
        if (!empty($this->returnRecipient)) {
            return $this->returnRecipient;
        }

        $returnAddress = $this->moduleConfig->getReturnAddress($this->getStoreId());
        if (empty($returnAddress)) {
            $this->returnRecipient = $this->getCoreExtractor()->getReturnRecipient();
        } else {
            $this->returnRecipient = $this->shipperFactory->create(
                [
                    'contactPersonName' => '',
                    'contactPersonFirstName' => '',
                    'contactPersonLastName' => '',
                    'contactCompanyName' => $returnAddress['company'],
                    'contactEmail' => '',
                    'contactPhoneNumber' => '',
                    'street' => [$returnAddress['street_name'] . ' ' . $returnAddress['street_number']],
                    'city' => $returnAddress['city'],
                    'state' => '',
                    'postalCode' => $returnAddress['postcode'],
                    'countryCode' => $returnAddress['country_id'],
                    'streetName' => $returnAddress['street_name'],
                    'streetNumber' => $returnAddress['street_number'],
                    'addressAddition' => '',
                ]
            );
        }

        return $this->returnRecipient;
    }

    public function getRecipient(): RecipientInterface
    {
        return $this->getCoreExtractor()->getRecipient();
    }

    public function getPackageWeight(): float
    {
        return $this->getCoreExtractor()->getPackageWeight();
    }

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
            $additionalData['termsOfTrade'] = $customsParams['termsOfTrade'] ?? '';
            $additionalData['customsFees'] = (float)($customsParams['customsFees'] ?? '');
            $additionalData['placeOfCommittal'] = $customsParams['placeOfCommittal'] ?? '';
            $additionalData['permitNumber'] = $customsParams['permitNumber'] ?? '';
            $additionalData['attestationNumber'] = $customsParams['attestationNumber'] ?? '';
            $additionalData['electronicExportNotification'] = $customsParams['electronicExportNotification'] ?? false;
            $additionalData['sendersCustomsReference'] = $customsParams['sendersCustomsReference'] ?? '';
            $additionalData['addresseesCustomsReference'] = $customsParams['addresseesCustomsReference'] ?? '';

            try {
                // create new extended package instance with paket-specific export data
                $paketPackages[$packageId] = $this->packageFactory->create(
                    [
                        'productCode' => $package->getProductCode(),
                        'containerType' => $package->getContainerType(),
                        'weightUom' => $package->getWeightUom(),
                        'dimensionsUom' => $package->getDimensionsUom(),
                        'weight' => $package->getWeight(),
                        'length' => $package->getLength(),
                        'width' => $package->getWidth(),
                        'height' => $package->getHeight(),
                        'customsValue' => $package->getCustomsValue(),
                        'contentType' => $package->getContentType(),
                        'contentExplanation' => $package->getContentExplanation(),
                        'packageAdditional' => $this->packageAdditionalFactory->create($additionalData),
                    ]
                );
            } catch (\Exception $exception) {
                throw new LocalizedException(__('An error occurred while preparing package data.'), $exception);
            }
        }

        return $paketPackages;
    }

    public function getAllItems(): array
    {
        return $this->getCoreExtractor()->getAllItems();
    }

    public function getPackageItems(): array
    {
        return $this->getCoreExtractor()->getPackageItems();
    }

    public function isCashOnDelivery(): bool
    {
        return $this->coreExtractor->isCashOnDelivery();
    }

    public function getCodReasonForPayment(): string
    {
        return $this->coreExtractor->getCodReasonForPayment();
    }

    public function isPickupLocationDelivery(): bool
    {
        return $this->getCoreExtractor()->isPickupLocationDelivery();
    }

    public function getDeliveryLocationType(): string
    {
        return $this->coreExtractor->getDeliveryLocationType();
    }

    public function getDeliveryLocationId(): string
    {
        return $this->coreExtractor->getDeliveryLocationId();
    }

    public function getDeliveryLocationNumber(): string
    {
        return $this->coreExtractor->getDeliveryLocationNumber();
    }

    public function getDeliveryLocationCountryCode(): string
    {
        return $this->coreExtractor->getDeliveryLocationCountryCode();
    }

    public function getDeliveryLocationPostalCode(): string
    {
        return $this->coreExtractor->getDeliveryLocationPostalCode();
    }

    public function getDeliveryLocationCity(): string
    {
        return $this->coreExtractor->getDeliveryLocationCity();
    }

    public function getDeliveryLocationStreet(): string
    {
        return $this->coreExtractor->getDeliveryLocationStreet();
    }

    /**
     * Obtain the merchant's DHL address book reference for the shipment sender.
     *
     * In sandbox mode, a sandbox account is used that has no address book
     * or at least we are not aware of the available entries.
     *
     * @return string
     */
    public function getSenderReference(): string
    {
        $storeId = $this->getStoreId();
        if ($this->moduleConfig->isSandboxMode($storeId)) {
            return '';
        }

        return $this->moduleConfig->getSenderReference($storeId);
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

        if (!$this->moduleConfig->isSandboxMode($storeId)) {
            $ekp = $this->moduleConfig->getEkp($storeId);

            $participations = $this->moduleConfig->getParticipations($storeId);
            $participation = $participations[$procedure] ?? '';
        } elseif ($this->moduleConfig->getShippingApiType($storeId) === ModuleConfig::SHIPPING_API_SOAP) {
            $ekp = '2222222222';
            $participation = ($productCode === ShippingProducts::CODE_NATIONAL) ? '04' : '01';
        } else {
            $ekp = '3333333333';
            $participation = ($productCode === ShippingProducts::CODE_NATIONAL) ? '02' : '01';
        }

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
        $packages = $this->getPackages();

        /** @var PackageInterface $package */
        $package = array_shift($packages);

        $storeId = $this->getCoreExtractor()->getStoreId();
        $productCode = $package->getProductCode();
        $procedure = $this->shippingProducts->getReturnProcedure($productCode);

        if (!$this->moduleConfig->isSandboxMode($storeId)) {
            $ekp = $this->moduleConfig->getEkp($storeId);

            $participations = $this->moduleConfig->getParticipations($storeId);
            $participation = $participations[$procedure] ?? '';

            $billingNumber = $ekp . $procedure . $participation;
        } elseif ($this->moduleConfig->getShippingApiType($storeId) === ModuleConfig::SHIPPING_API_SOAP) {
            $billingNumber = "2222222222{$procedure}01";
        } else {
            $billingNumber = "3333333333{$procedure}01";
        }

        return $billingNumber;
    }

    public function getCustomerAccountNumber(): string
    {
        return (string) $this->getServiceOptionReader()->getServiceOptionValue(
            \Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes::SERVICE_OPTION_DELIVERY_LOCATION,
            Codes::SERVICE_INPUT_DELIVERY_LOCATION_ACCOUNT_NUMBER
        );
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

        if ($this->isPickupLocationDelivery()) {
            $postNumber = $this->getCustomerAccountNumber();
            $locationType = $this->getDeliveryLocationType();

            $locationTypes = [LocationInterface::TYPE_POSTOFFICE, LocationInterface::TYPE_SERVICEPOINT];
            if (empty($postNumber) && in_array($locationType, $locationTypes)) {
                // Postal Office and Parcel Shop delivery with no post number require email address
                return true;
            }
        }

        return false;
    }

    /**
     * Check if recipient phone number must be set.
     *
     * @return bool
     */
    public function isRecipientPhoneRequired(): bool
    {
        $recipientCountry = $this->getDeliveryLocationCountryCode() ?: $this->getRecipient()->getCountryCode();
        if ($recipientCountry !== $this->getShipper()->getCountryCode()) {
            return $this->moduleConfig->isPhoneNumberTransmissionEnabled($this->getCoreExtractor()->getStoreId());
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
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_BULKY_GOODS);
    }

    /**
     * Obtain the "additionalInsurance" flag for the current package.
     *
     * @return bool
     */
    public function isAdditionalInsurance(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_INSURANCE);
    }

    /**
     * Check if preferredDay has been booked
     *
     * @return bool
     */
    public function hasPreferredDay(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_PREFERRED_DAY);
    }

    /**
     * Obtain the "preferredDay" value for the current package.
     *
     * @return string
     */
    public function getPreferredDay(): string
    {
        return (string)$this->getServiceOptionReader()->getServiceOptionValue(
            Codes::SERVICE_OPTION_PREFERRED_DAY,
            'date'
        );
    }

    /**
     * Check if neighbor delivery was booked
     *
     * @return bool
     */
    public function hasNeighborDelivery(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_NEIGHBOR_DELIVERY);
    }

    /**
     * Obtain the name and address details of the selected neighbor for the current package.
     *
     * @return string
     */
    public function getNeighborDetails(): string
    {
        $name = (string)$this->getServiceOptionReader()->getServiceOptionValue(
            Codes::SERVICE_OPTION_NEIGHBOR_DELIVERY,
            'name'
        );
        $address = (string)$this->getServiceOptionReader()->getServiceOptionValue(
            Codes::SERVICE_OPTION_NEIGHBOR_DELIVERY,
            'address'
        );

        return trim("$name $address");
    }

    /**
     * Check if parcel drop-off was booked
     *
     * @return bool
     */
    public function hasDropOffLocation(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_DROPOFF_DELIVERY);
    }

    /**
     * Obtain the drop-off location for the current package.
     *
     * @return string
     */
    public function getDropOffLocation(): string
    {
        return (string)$this->getServiceOptionReader()->getServiceOptionValue(
            Codes::SERVICE_OPTION_DROPOFF_DELIVERY,
            'details'
        );
    }

    /**
     * Obtain the "visualCheckOfAge" value for the current package.
     *
     * @return string
     */
    public function getVisualCheckOfAge(): string
    {
        return $this->getServiceOptionReader()->getServiceOptionValue(
            Codes::SERVICE_OPTION_CHECK_OF_AGE,
            'details'
        );
    }

    /**
     * Obtain the "personally" flag for the package.
     *
     * @return bool
     */
    public function isNamedPersonOnly(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_NAMED_PERSON_ONLY);
    }

    /**
     * Obtain the "parcelAnnouncement" flag for the current package.
     *
     * @return bool
     */
    public function isParcelAnnouncement(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_PARCEL_ANNOUNCEMENT);
    }

    /**
     * Obtain the "returnShipment" flag for the current package.
     *
     * @return bool
     */
    public function isReturnShipment(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_RETURN_SHIPMENT);
    }

    /**
     * Obtain parcel outlet routing notification email.
     *
     * @return string Empty string if service is not enabled, email address otherwise.
     */
    public function getParcelOutletRoutingEmail(): string
    {
        if (!$this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_PARCEL_OUTLET_ROUTING)) {
            return '';
        }

        $email = $this->getServiceOptionReader()->getServiceOptionValue(
            Codes::SERVICE_OPTION_PARCEL_OUTLET_ROUTING,
            Codes::SERVICE_INPUT_PARCEL_OUTLET_ROUTING_NOTIFICATION_EMAIL
        );

        return $email ?: $this->getRecipient()->getContactEmail();
    }

    /**
     * Obtain the "premium" flag for the current package.
     *
     * @return bool
     */
    public function isPremium(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_PREMIUM);
    }

    /**
     * Obtain the shipment endorsement type for the current package.
     *
     * @return string
     */
    public function getEndorsement(): string
    {
        return $this->getServiceOptionReader()->getServiceOptionValue(
            Codes::SERVICE_OPTION_ENDORSEMENT,
            'details'
        );
    }

    /**
     * Check if no neighbor delivery was booked.
     *
     * @return bool
     */
    public function isNoNeighborDelivery(): bool
    {
        return $this->getServiceOptionReader()->isServiceEnabled(Codes::SERVICE_OPTION_NO_NEIGHBOR_DELIVERY);
    }
}
