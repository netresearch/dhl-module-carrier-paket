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

    // Defaults
    public const CONFIG_PATH_VERSION = 'carriers/dhlpaket/version';

    // 100_general_settings.xml
    public const CONFIG_PATH_CUT_OFF_TIMES = 'dhlshippingsolutions/dhlpaket/general_shipping_settings/cut_off_times';
    public const CONFIG_PATH_ENABLE_LOGGING = 'dhlshippingsolutions/dhlpaket/general_shipping_settings/logging';
    public const CONFIG_PATH_LOGLEVEL = 'dhlshippingsolutions/dhlpaket/general_shipping_settings/logging_group/loglevel';

    // 200_dhl_paket_account.xml
    public const CONFIG_PATH_SANDBOX_MODE = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode';
    public const CONFIG_PATH_USER = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/api_username';
    public const CONFIG_PATH_SIGNATURE = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/api_password';
    public const CONFIG_PATH_EKP = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/account_number';
    public const CONFIG_PATH_PARTICIPATIONS = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/account_participations';

    public const CONFIG_PATH_AUTH_USERNAME = 'dhlshippingsolutions/dhlpaket/account_settings/auth_username';
    public const CONFIG_PATH_AUTH_PASSWORD = 'dhlshippingsolutions/dhlpaket/account_settings/auth_password';
    public const CONFIG_PATH_SANDBOX_AUTH_USERNAME = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_auth_username';
    public const CONFIG_PATH_SANDBOX_AUTH_PASSWORD = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_auth_password';
    public const CONFIG_PATH_SANDBOX_USER = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_username';
    public const CONFIG_PATH_SANDBOX_SIGNATURE = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_password';
    public const CONFIG_PATH_SANDBOX_EKP = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_account_number';
    public const CONFIG_PATH_SANDBOX_PARTICIPATIONS = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_account_participations';

    // 400_checkout_presentation.xml
    public const CONFIG_PATH_METHOD_NAME = 'carriers/dhlpaket/name';
    public const CONFIG_PATH_PROXY_CARRIER = 'dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier';

    // 500_shipment_defaults.xml
    public const CONFIG_PATH_PRINT_ONLY_IF_CODEABLE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/print_only_if_codeable';
    public const CONFIG_PATH_SENDER_REFERENCE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/sender_address_book_reference';
    public const CONFIG_PATH_PRINT_RECEIVER_CONTACT = 'dhlshippingsolutions/dhlpaket/shipment_defaults/print_receiver_contact';
    public const CONFIG_PATH_SHIPPING_PRODUCTS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/shipping_products';
    public const CONFIG_PATH_CUSTOMS_REFERENCE_NUMBERS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/customs/reference_numbers';
    public const CONFIG_PATH_PLACE_OF_COMMITTAL = 'dhlshippingsolutions/dhlpaket/shipment_defaults/customs/place_of_committal';
    public const CONFIG_PATH_ELECTRONIC_EXPORT_NOTIFICATION = 'dhlshippingsolutions/dhlpaket/shipment_defaults/customs/electronic_export_notification';
    public const CONFIG_PATH_VISUAL_CHECK_OF_AGE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/visual_check_of_age';
    public const CONFIG_PATH_RETURN_SHIPMENT = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment';
    public const CONFIG_PATH_RETURN_RECEIVER = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment_receiver';
    public const CONFIG_PATH_PREMIUM = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/premium';
    public const CONFIG_PATH_ENDORSEMENT = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/endorsement';
    public const CONFIG_PATH_ADDITIONAL_INSURANCE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/additional_insurance';
    public const CONFIG_PATH_BULKY_GOODS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/bulky_goods';
    public const CONFIG_PATH_PARCEL_OUTLET = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/parcel_outlet';
    public const CONFIG_PATH_PARCEL_OUTLET_NOTIFICATION_EMAIL = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/parcel_outlet_notification_email';

    // 600_additional_services.xml
    public const CONFIG_PATH_PARCEL_ANNOUNCEMENT = 'dhlshippingsolutions/dhlpaket/additional_services/parcelannouncement';
    public const CONFIG_PATH_PARCEL_STATION_DELIVERY = 'dhlshippingsolutions/dhlpaket/additional_services/deliverylocation';
    public const CONFIG_PATH_PREFERRED_LOCATION = 'dhlshippingsolutions/dhlpaket/additional_services/preferredlocation';
    public const CONFIG_PATH_PREFERRED_NEIGHBOR = 'dhlshippingsolutions/dhlpaket/additional_services/preferredneighbour';
    public const CONFIG_PATH_PREFERRED_DAY = 'dhlshippingsolutions/dhlpaket/additional_services/preferredday';
    public const CONFIG_PATH_PREFERRED_DAY_CHARGE = 'dhlshippingsolutions/dhlpaket/additional_services/preferredday_charge';

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
     * Get the HTTP basic authentication username (CIG application authentication).
     *
     * @param mixed $store
     * @return string
     */
    public function getAuthUsername($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxAuthUsername($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_AUTH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the HTTP basic authentication password (CIG application authentication).
     *
     * @param mixed $store
     * @return string
     */
    public function getAuthPassword($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxAuthPassword($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_AUTH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's name (API user credentials).
     *
     * @param mixed $store
     * @return string
     */
    public function getUser($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxUser($store);
        }

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
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxSignature($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SIGNATURE,
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
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxEkp($store);
        }

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
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxParticipations($store);
        }

        $participations = $this->scopeConfig->getValue(
            self::CONFIG_PATH_PARTICIPATIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($participations, 'participation', 'procedure');
    }

    /**
     * Get the user's participation number for a given procedure.
     *
     * @param string $procedure
     * @param mixed $store
     * @return string
     */
    public function getParticipation(string $procedure, $store = null): string
    {
        return $this->getParticipations($store)[$procedure] ?? '';
    }

    /**
     * Get the HTTP basic sandbox authentication username (CIG application authentication).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxAuthUsername($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SANDBOX_AUTH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the HTTP basic sandbox authentication password (CIG application authentication).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxAuthPassword($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SANDBOX_AUTH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's name (API user sandbox credentials).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxUser($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SANDBOX_USER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's password (API user sandbox credentials).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxSignature($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SANDBOX_SIGNATURE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's sandbox EKP (standardized customer and product number).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxEkp($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SANDBOX_EKP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's sandbox participation numbers (partner IDs).
     *
     * @param mixed $store
     * @return string[]
     */
    private function getSandboxParticipations($store = null): array
    {
        $participations = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SANDBOX_PARTICIPATIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($participations, 'participation', 'procedure');
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
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_PATH_SENDER_REFERENCE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if the receiver phone number should be printed on the shipping label.
     *
     * @param mixed $store
     * @return bool
     */
    public function isContactPrintingEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_PRINT_RECEIVER_CONTACT,
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
