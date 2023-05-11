<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Netresearch\ShippingCore\Api\InfoBox\VersionInterface;

class ModuleConfig implements VersionInterface
{
    // phpcs:disable Generic.Files.LineLength.TooLong

    public const SHIPPING_API_REST = 'REST';
    public const SHIPPING_API_SOAP = 'SOAP';

    // Defaults
    public const CONFIG_PATH_VERSION = 'carriers/dhlpaket/version';

    // 100_general_settings.xml
    public const CONFIG_PATH_CUT_OFF_TIMES = 'dhlshippingsolutions/dhlpaket/general_shipping_settings/cut_off_times';
    public const CONFIG_PATH_ENABLE_LOGGING = 'dhlshippingsolutions/dhlpaket/general_shipping_settings/logging';
    public const CONFIG_PATH_LOGLEVEL = 'dhlshippingsolutions/dhlpaket/general_shipping_settings/logging_group/loglevel';

    // 200_dhl_paket_account.xml
    public const CONFIG_PATH_SANDBOX_MODE = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode';
    public const CONFIG_PATH_API_TYPE = 'dhlshippingsolutions/dhlpaket/account_settings/api_type';

    // production settings
    public const CONFIG_PATH_USER = 'dhlshippingsolutions/dhlpaket/account_settings/production_group/auth_username';
    public const CONFIG_PATH_PASS = 'dhlshippingsolutions/dhlpaket/account_settings/production_group/auth_password';
    public const CONFIG_PATH_EKP = 'dhlshippingsolutions/dhlpaket/account_settings/production_group/account_number';
    public const CONFIG_PATH_PARTICIPATIONS = 'dhlshippingsolutions/dhlpaket/account_settings/production_group/participations';

    // 400_checkout_presentation.xml
    public const CONFIG_PATH_METHOD_NAME = 'carriers/dhlpaket/name';
    public const CONFIG_PATH_PROXY_CARRIER = 'dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier';

    // 500_shipment_defaults.xml
    public const CONFIG_PATH_PRINT_ONLY_IF_CODEABLE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/print_only_if_codeable';
    public const CONFIG_PATH_GROUP_PROFILE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/group_profile';
    public const CONFIG_PATH_SENDER_REFERENCE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/sender_address_book_reference';
    public const CONFIG_PATH_SEND_RECEIVER_PHONE_NUMBER = 'dhlshippingsolutions/dhlpaket/shipment_defaults/send_receiver_phone_number';
    public const CONFIG_PATH_SHIPPING_PRODUCTS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/shipping_products';
    public const CONFIG_PATH_LABEL_FORMAT = 'dhlshippingsolutions/dhlpaket/shipment_defaults/print/format';
    public const CONFIG_PATH_LABEL_FORMAT_RETURN = 'dhlshippingsolutions/dhlpaket/shipment_defaults/print/format_return';
    public const CONFIG_PATH_CUSTOMS_REFERENCE_NUMBERS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/customs/reference_numbers';
    public const CONFIG_PATH_PLACE_OF_COMMITTAL = 'dhlshippingsolutions/dhlpaket/shipment_defaults/customs/place_of_committal';
    public const CONFIG_PATH_ELECTRONIC_EXPORT_NOTIFICATION = 'dhlshippingsolutions/dhlpaket/shipment_defaults/customs/electronic_export_notification';
    public const CONFIG_PATH_VISUAL_CHECK_OF_AGE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/visual_check_of_age';
    public const CONFIG_PATH_DELIVERY_TYPE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/delivery_type';
    public const CONFIG_PATH_NAMED_PERSON_ONLY = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/named_person_only';
    public const CONFIG_PATH_EXCLUDE_NEIGHBOR_DELIVERY = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/no_neighbor_delivery';
    public const CONFIG_PATH_PARCEL_OUTLET = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/parcel_outlet';
    public const CONFIG_PATH_PARCEL_OUTLET_NOTIFICATION_EMAIL = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/parcel_outlet_notification_email';
    public const CONFIG_PATH_ADDITIONAL_INSURANCE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/additional_insurance';
    public const CONFIG_PATH_BULKY_GOODS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/bulky_goods';
    public const CONFIG_PATH_PDDP = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/delivery_duty_paid';
    public const CONFIG_PATH_ENDORSEMENT = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/endorsement';
    public const CONFIG_PATH_RETURN_SHIPMENT = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment';
    public const CONFIG_PATH_RETURN_RECEIVER = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment_receiver';

    // 600_additional_services.xml
    public const CONFIG_PATH_PARCEL_ANNOUNCEMENT = 'dhlshippingsolutions/dhlpaket/additional_services/parcelannouncement';
    public const CONFIG_PATH_PARCEL_STATION_DELIVERY = 'dhlshippingsolutions/dhlpaket/additional_services/deliverylocation';
    public const CONFIG_PATH_CLOSEST_DROP_POINT = 'dhlshippingsolutions/dhlpaket/additional_services/closestdroppoint';
    public const CONFIG_PATH_CLOSEST_DROP_POINT_CHARGE = 'dhlshippingsolutions/dhlpaket/additional_services/closestdroppoint_charge';

    public const CONFIG_PATH_PREFERRED_LOCATION = 'dhlshippingsolutions/dhlpaket/additional_services/preferredlocation';
    public const CONFIG_PATH_PREFERRED_NEIGHBOR = 'dhlshippingsolutions/dhlpaket/additional_services/preferredneighbour';
    public const CONFIG_PATH_PREFERRED_DAY = 'dhlshippingsolutions/dhlpaket/additional_services/preferredday';
    public const CONFIG_PATH_PREFERRED_DAY_CHARGE = 'dhlshippingsolutions/dhlpaket/additional_services/preferredday_charge';
    public const CONFIG_PATH_NO_NEIGHBOR_DELIVERY = 'dhlshippingsolutions/dhlpaket/additional_services/no_neighbor_delivery';
    public const CONFIG_PATH_NO_NEIGHBOR_DELIVERY_CHARGE = 'dhlshippingsolutions/dhlpaket/additional_services/no_neighbor_delivery_charge';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getModuleVersion(): string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_VERSION);
    }

    /**
     * Obtain the carrier method name for checkout presentation.
     *
     * @param mixed $store
     * @return string
     */
    public function getMethodName($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_METHOD_NAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Obtain the list of cut-off times.
     *
     * @param mixed $store
     * @return string[] Times (H:i), indexed by ISO-8601 day of week
     */
    public function getCutOffTimes($store = null): array
    {
        $cutOffTimes = $this->scopeConfig->getValue(
            self::CONFIG_PATH_CUT_OFF_TIMES,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($cutOffTimes, 'time', 'day');
    }

    /**
     * Get the code of the carrier to forward rate requests to.
     *
     * @param mixed $store
     * @return string
     */
    public function getProxyCarrierCode($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_PROXY_CARRIER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns true if sandbox mode is enabled.
     *
     * @param mixed $store
     * @return bool
     */
    public function isSandboxMode($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SANDBOX_MODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the configured shipping API.
     *
     * @see self::SHIPPING_API_REST
     * @see self::SHIPPING_API_SOAP
     *
     * @return string
     */
    public function getShippingApiType(): string
    {
        return (string) $this->scopeConfig->getValue(self::CONFIG_PATH_API_TYPE);
    }

    /**
     * Get the user's name (API user credentials).
     *
     * @param mixed $store
     * @return string
     */
    public function getUser($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_USER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's password (API user credentials).
     *
     * @param mixed $store
     * @return string
     */
    public function getSignature($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_PASS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's EKP (standardized customer and product number).
     *
     * @param mixed $store
     * @return string
     */
    public function getEkp($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_EKP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's participation numbers (partner IDs).
     *
     * @param mixed $store
     * @return string[]
     */
    public function getParticipations($store = null): array
    {
        $participations = $this->scopeConfig->getValue(
            self::CONFIG_PATH_PARTICIPATIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($participations, 'participation', 'procedure');
    }

    public function isPrintOnlyIfCodeable($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_PRINT_ONLY_IF_CODEABLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the group profile name.
     *
     * The selected group profile defines the billing numbers that are available for creating shipments.
     * Group profiles can be created in the DHL Business Customer Portal.
     *
     * @param mixed $store
     * @return string
     */
    public function getGroupProfile($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_GROUP_PROFILE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the sender address book reference.
     *
     * The DHL Business Customer Portal sender address book reference can be
     * transferred during label requests instead of a shipper address. If no
     * reference is set, then the shipping origin address from the
     * "Shipping Settings" section would typically be used.
     *
     * @param mixed $store
     * @return string
     */
    public function getSenderReference($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SENDER_REFERENCE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if the receiver phone number should be sent to DHL.
     *
     * @param mixed $store
     * @return bool
     */
    public function isPhoneNumberTransmissionEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_SEND_RECEIVER_PHONE_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get default product per destination, e.g.
     *
     * - ["DE" => ["DE" => "V01PAK", "EU" => "V53PAK", "INTL" => "V53PAK"]]
     *
     * @param mixed $store
     * @return string[][]
     */
    public function getDefaultProducts($store = null): array
    {
        $products = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SHIPPING_PRODUCTS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $defaultProducts = [];
        $products = array_column($products, 'product', 'route');
        foreach ($products as $route => $product) {
            $locations = explode('-', $route);
            $defaultProducts[$locations[0]][$locations[1]] = $product;
        }

        return $defaultProducts;
    }

    /**
     * Get the shipment label format.
     *
     * Sending a print format to the API overwrites the DHL Business Customer Portal group profile setting.
     *
     * @param mixed $store
     * @return string
     */
    public function getLabelFormat($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_LABEL_FORMAT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the return shipment label format.
     *
     * Sending a print format to the API overwrites the DHL Business Customer Portal group profile setting.
     *
     * @param mixed $store
     * @return string
     */
    public function getLabelFormatReturn($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_LABEL_FORMAT_RETURN,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Obtain address to be used for enclosed return shipment labels.
     *
     * - company
     * - country_id
     * - postcode
     * - city
     * - street_name
     * - street_number
     *
     * @param mixed $store
     * @return string[] Address details if ALL values are configured, empty array otherwise.
     */
    public function getReturnAddress($store = null): array
    {
        $fields = ['company', 'country_id', 'postcode', 'city', 'street_name', 'street_number'];
        $address = array_filter(
            (array)$this->scopeConfig->getValue(
                self::CONFIG_PATH_RETURN_RECEIVER,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );

        $diff = array_diff($fields, array_keys($address));
        if (empty($diff)) {
            return $address;
        }

        return [];
    }

    /**
     * @param mixed $store
     * @return float
     */
    public function getClosestDropPointAdditionalCharge($store = null): float
    {
        return (float) str_replace(
            ',',
            '.',
            $this->scopeConfig->getValue(
                self::CONFIG_PATH_CLOSEST_DROP_POINT_CHARGE,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * @param mixed $store
     * @return float
     */
    public function getPreferredDayAdditionalCharge($store = null): float
    {
        return (float) str_replace(
            ',',
            '.',
            $this->scopeConfig->getValue(
                self::CONFIG_PATH_PREFERRED_DAY_CHARGE,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * Check if No Neighbor Delivery service is enabled for checkout.
     *
     * @param mixed $store
     * @return bool
     */
    public function isNoNeighborDeliveryEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_NO_NEIGHBOR_DELIVERY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param mixed $store
     * @return float
     */
    public function getNoNeighborDeliveryAdditionalCharge($store = null): float
    {
        return (float) str_replace(
            ',',
            '.',
            $this->scopeConfig->getValue(
                self::CONFIG_PATH_NO_NEIGHBOR_DELIVERY_CHARGE,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * Get the shipper's customs reference number per destination country.
     *
     * Example: ["CH" => "123232", "US" => "555666"]
     *
     * @param mixed $store
     * @return string[]
     */
    public function getCustomsReferenceNumbers($store = null): array
    {
        $customsReferences = $this->scopeConfig->getValue(
            self::CONFIG_PATH_CUSTOMS_REFERENCE_NUMBERS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($customsReferences, 'customs_reference', 'country');
    }
}
