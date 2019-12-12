<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ModuleConfig
 *
 * @package Dhl\Paket\Model
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ModuleConfig
{
    // Defaults
    const CONFIG_PATH_VERSION = 'carriers/dhlpaket/version';

    // 100_general_settings.xml
    const CONFIG_PATH_ENABLE_LOGGING = 'dhlshippingsolutions/dhlpaket/general_shipping_settings/logging';
    const CONFIG_PATH_LOGLEVEL = 'dhlshippingsolutions/dhlpaket/general_shipping_settings/logging_group/loglevel';

    // 200_dhl_paket_account.xml
    const CONFIG_PATH_SANDBOX_MODE = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode';
    const CONFIG_PATH_USER = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/api_username';
    const CONFIG_PATH_SIGNATURE = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/api_password';
    const CONFIG_PATH_EKP = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/account_number';
    const CONFIG_PATH_PARTICIPATIONS = 'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/account_participations';

    const CONFIG_PATH_AUTH_USERNAME = 'dhlshippingsolutions/dhlpaket/account_settings/auth_username';
    const CONFIG_PATH_AUTH_PASSWORD = 'dhlshippingsolutions/dhlpaket/account_settings/auth_password';
    const CONFIG_PATH_SANDBOX_AUTH_USERNAME = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_auth_username';
    const CONFIG_PATH_SANDBOX_AUTH_PASSWORD = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_auth_password';
    const CONFIG_PATH_SANDBOX_USER = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_username';
    const CONFIG_PATH_SANDBOX_SIGNATURE = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_password';
    const CONFIG_PATH_SANDBOX_EKP = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_account_number';
    const CONFIG_PATH_SANDBOX_PARTICIPATIONS = 'dhlshippingsolutions/dhlpaket/account_settings/sandbox_account_participations';

    // 400_checkout_presentation.xml
    const CONFIG_PATH_PROXY_CARRIER = 'dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier';

    // 500_shipment_defaults.xml
    const CONFIG_PATH_PRINT_ONLY_IF_CODEABLE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/print_only_if_codeable';
    const CONFIG_PATH_ADDITIONAL_FEE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/additional_fee';
    const CONFIG_PATH_PLACE_OF_COMMITTAL = 'dhlshippingsolutions/dhlpaket/shipment_defaults/place_of_committal';
    const CONFIG_PATH_EXCLUDED_DROPOFFDAYS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/drop_off_days';
    const CONFIG_PATH_DEFAULT_PRODUCTS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/shipping_products';
    const CONFIG_PATH_ELECTRONIC_EXPORT_NOTIFICATION = 'dhlshippingsolutions/dhlpaket/shipment_defaults/electronic_export_notification';
    const CONFIG_PATH_VISUAL_CHECK_OF_AGE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/visual_check_of_age';
    const CONFIG_PATH_RETURN_SHIPMENT = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/return_shipment';
    const CONFIG_PATH_ADDITIONAL_INSURANCE = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/additional_insurance';
    const CONFIG_PATH_BULKY_GOODS = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/bulky_goods';
    const CONFIG_PATH_PARCEL_OUTLET = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/parcel_outlet';

    // 600_additional_services.xml
    const CONFIG_PATH_ADDITIONAL_SERVICES = 'dhlshippingsolutions/dhlpaket/additional_services/services_group';
    const CONFIG_PATH_PARCEL_ANNOUNCEMENT = 'dhlshippingsolutions/dhlpaket/additional_services/services_group/parcelannouncement';
    const CONFIG_PATH_PREFERRED_LOCATION = 'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredlocation';
    const CONFIG_PATH_PREFERRED_NEIGHBOUR = 'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredneighbour';
    const CONFIG_PATH_PREFERRED_DAY_ = 'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredday';
    const CONFIG_PATH_PREFERRED_TIME = 'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredtime';
    const CONFIG_PATH_TIME_CHARGE = 'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredTimeCharge';
    const CONFIG_PATH_DAY_CHARGE = 'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredDayCharge';
    const CONFIG_PATH_COMBINED_CHARGE = 'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredCombinedCharge';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ModuleConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getModuleVersion(): string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_VERSION);
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
     * Returns TRUE if the "print only if codeable" service should be used or not.
     *
     * @param mixed $store
     *
     * @return bool
     */
    public function printOnlyIfCodeable($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_PATH_PRINT_ONLY_IF_CODEABLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the selected "visual check of age" service which should be used.
     *
     * @param mixed $store
     *
     * @return string
     */
    public function visualCheckOfAge($store = null): string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_VISUAL_CHECK_OF_AGE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns TRUE if the "return shipment" service should be used or not.
     *
     * @param mixed $store
     *
     * @return bool
     */
    public function returnShipment($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_PATH_RETURN_SHIPMENT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns TRUE if the "additional insurance" service should be used or not.
     *
     * @param mixed $store
     *
     * @return bool
     */
    public function additionalInsurance($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_PATH_ADDITIONAL_INSURANCE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns TRUE if the "bulky goods" service should be used or not.
     *
     * @param mixed $store
     *
     * @return bool
     */
    public function bulkyGoods($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_PATH_BULKY_GOODS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get enable config values for all checkout services
     *
     * @param mixed $store
     * @return array
     */
    public function getCheckoutServices($store = null): array
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_ADDITIONAL_SERVICES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns TRUE if the "parcel outlet" service should be used or not.
     *
     * @param mixed $store
     *
     * @return bool
     */
    public function parcelOutlet($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_PATH_PARCEL_OUTLET,
            ScopeInterface::SCOPE_STORE,
            $store
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
                self::CONFIG_PATH_DAY_CHARGE,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * @param mixed $store
     * @return float
     */
    public function getPreferredTimeAdditionalCharge($store = null): float
    {
        return (float) str_replace(
            ',',
            '.',
            $this->scopeConfig->getValue(
                self::CONFIG_PATH_TIME_CHARGE,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * @param mixed $store
     * @return float
     */
    public function getPreferredCombinedCharge($store = null): float
    {
        return (float) str_replace(
            ',',
            '.',
            $this->scopeConfig->getValue(
                self::CONFIG_PATH_COMBINED_CHARGE,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * Obtain drop off days from config.
     *
     * @param mixed $store
     *
     * @return string[]
     */
    public function getExcludedDropOffDays($store = null): array
    {
        $dropOffDays = $this->scopeConfig->getValue(
            self::CONFIG_PATH_EXCLUDED_DROPOFFDAYS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $dropOffDays === null ? [] : explode(',', $dropOffDays);
    }
}
