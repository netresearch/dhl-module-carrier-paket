<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Helper\Carrier;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ModuleConfig
 *
 * @package Dhl\Paket\Model
 * @link      http://www.netresearch.de/
 */
class ModuleConfig
{
    const CONFIG_ROOT = 'carriers/dhlpaket/';

    // Defaults
    const CONFIG_XML_PATH_ENABLED = self::CONFIG_ROOT . 'active';
    const CONFIG_XML_PATH_SHIP_TO_SPECIFIC_COUNTRIES = self::CONFIG_ROOT . 'sallowspecific';
    const CONFIG_XML_PATH_SPECIFIC_COUNTRIES = self::CONFIG_ROOT . 'specificcountry';
    const CONFIG_XML_PATH_SHOW_IF_NOT_APPLICABLE = self::CONFIG_ROOT . 'showmethod';
    const CONFIG_XML_PATH_ERROR_MESSAGE = self::CONFIG_ROOT . 'specificerrmsg';
    const CONFIG_XML_PATH_TITLE = self::CONFIG_ROOT . 'title';
    const CONFIG_XML_PATH_SORT_ORDER = self::CONFIG_ROOT . 'sort_order';

    // 100_general_settings.xml
    const CONFIG_XML_PATH_ENABLE_LOGGING = self::CONFIG_ROOT . 'general_shipping_settings/logging';
    const CONFIG_XML_PATH_LOGLEVEL = self::CONFIG_ROOT . 'general_shipping_settings/loglevel';

    // 200_dhl_paket_account.xml
    const CONFIG_XML_PATH_AUTH_USERNAME = self::CONFIG_ROOT . 'account_settings/auth_username';
    const CONFIG_XML_PATH_AUTH_PASSWORD = self::CONFIG_ROOT . 'account_settings/auth_password';
    const CONFIG_XML_PATH_SANDBOX_MODE = self::CONFIG_ROOT . 'account_settings/sandboxmode';

    const CONFIG_XML_PATH_API_USERNAME = self::CONFIG_ROOT . 'account_settings/api_username';
    const CONFIG_XML_PATH_API_PASSWORD = self::CONFIG_ROOT . 'account_settings/api_password';
    const CONFIG_XML_PATH_API_ACCOUNT_NUMBER = self::CONFIG_ROOT . 'account_settings/account_number';
    const CONFIG_XML_PATH_API_ACCOUNT_PARTICIPATIONS = self::CONFIG_ROOT . 'account_settings/account_participations';

    const CONFIG_XML_PATH_API_SANDBOX_AUTH_USERNAME = self::CONFIG_ROOT . 'account_settings/sandbox_auth_username';
    const CONFIG_XML_PATH_API_SANDBOX_AUTH_PASSWORD = self::CONFIG_ROOT . 'account_settings/sandbox_auth_password';
    const CONFIG_XML_PATH_API_SANDBOX_USERNAME = self::CONFIG_ROOT . 'account_settings/sandbox_username';
    const CONFIG_XML_PATH_API_SANDBOX_PASSWORD = self::CONFIG_ROOT . 'account_settings/sandbox_password';
    const CONFIG_XML_PATH_API_SANDBOX_ACCOUNT_NUMBER = self::CONFIG_ROOT . 'account_settings/sandbox_account_number';
    const CONFIG_XML_PATH_API_SANDBOX_ACCOUNT_PARTICIPATIONS = self::CONFIG_ROOT . 'account_settings/sandbox_account_participations';

    // 400_checkout_presentation.xml
    const CONFIG_XML_PATH_EMULATED_CARRIER = self::CONFIG_ROOT . 'dhl_paket_checkout_settings/emulated_carrier';

    // 500_additional_services.xml
    const CONFIG_XML_PATH_PRINT_ONLY_IF_CODEABLE = self::CONFIG_ROOT . 'dhl_paket_additional_services/print_only_if_codeable';

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
     * Check whether the module is enabled or not.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the sort order.
     *
     * @param string|null $store
     *
     * @return int
     */
    public function getSortOrder($store = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_SORT_ORDER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the title.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getTitle($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_TITLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the emulated carrier.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getEmulatedCarrier($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_EMULATED_CARRIER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if shipping only to specific countries.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function shipToSpecificCountries($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_SHIP_TO_SPECIFIC_COUNTRIES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the specific countries that the carrier can ship to.
     *
     * @param string|null $store
     *
     * @return string[]
     */
    public function getSpecificCountries($store = null): array
    {
        $countries = $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_SPECIFIC_COUNTRIES,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return explode(',', $countries);
    }

    /**
     * Get the error message to show in checkout if there are no rates available.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getNotApplicableErrorMessage($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_ERROR_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the logging status.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function isLoggingEnabled($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_ENABLE_LOGGING,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the log level.
     *
     * @param string|null $store
     *
     * @return int
     */
    public function getLogLevel($store = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_LOGLEVEL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the authentication username.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getAuthUsername($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxAuthUsername($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_AUTH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the authentication password.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getAuthPassword($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxAuthPassword($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_AUTH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns true if sandbox mode is enabled.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function isSandboxMode($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_SANDBOX_MODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the API username.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getApiUsername($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxUsername($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the API password.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getApiPassword($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxPassword($store);
        }

        return (string)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the account number.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getAccountNumber($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxAccountNumber($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_ACCOUNT_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the participation numbers.
     *
     * @param string|null $store
     *
     * @return string[]
     */
    public function getAccountParticipations($store = null): array
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxAccountParticipations($store);
        }

        $participations = $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_ACCOUNT_PARTICIPATIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($participations, 'participation', 'procedure');
    }

    /**
     * Returns the participation number for a given procedure.
     *
     * @param string $procedure
     * @param string|null $store
     *
     * @return string
     */
    public function getAccountParticipation(string $procedure, $store = null): string
    {
        return $this->getAccountParticipations($store)[$procedure] ?? '';
    }

    /**
     * Returns the AUTH username in sandbox mode.
     *
     * @param string|null $store
     *
     * @return string
     */
    private function getSandboxAuthUsername($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_SANDBOX_AUTH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the AUTH password in sandbox mode.
     *
     * @param string|null $store
     *
     * @return string
     */
    private function getSandboxAuthPassword($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_SANDBOX_AUTH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the API username in sandbox mode.
     *
     * @param string|null $store
     *
     * @return string
     */
    private function getSandboxUsername($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_SANDBOX_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the API password in sandbox mode.
     *
     * @param string|null $store
     *
     * @return string
     */
    private function getSandboxPassword($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_SANDBOX_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the API account number in sandbox mode.
     *
     * @param string|null $store
     *
     * @return string
     */
    private function getSandboxAccountNumber($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_SANDBOX_ACCOUNT_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the participation numbers.
     *
     * @param string|null $store
     *
     * @return string[]
     */
    private function getSandboxAccountParticipations($store = null): array
    {
        $participations = $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_SANDBOX_ACCOUNT_PARTICIPATIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($participations, 'participation', 'procedure');
    }

    /**
     * Returns TRUE if the "print only if codeable" service should be used or not.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function printOnlyIfCodeable($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_PRINT_ONLY_IF_CODEABLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the EU countries list.
     *
     * @param mixed $store
     *
     * @return array
     */
    public function getEuCountryList($store = null): array
    {
        $euCountries = $this->scopeConfig->getValue(
            Carrier::XML_PATH_EU_COUNTRIES_LIST,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return explode(',', $euCountries);
    }
}
