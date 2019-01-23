<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Config;

/**
 * Interface ModuleConfigInterface
 */
interface ModuleConfigInterface
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

    const CONFIG_XML_PATH_API_SANDBOX_USERNAME = self::CONFIG_ROOT . 'account_settings/sandbox_username';
    const CONFIG_XML_PATH_API_SANDBOX_PASSWORD = self::CONFIG_ROOT . 'account_settings/sandbox_password';
    const CONFIG_XML_PATH_API_SANDBOX_ACCOUNT_NUMBER = self::CONFIG_ROOT . 'account_settings/sandbox_account_number';
    const CONFIG_XML_PATH_API_SANDBOX_ACCOUNT_PARTICIPATIONS = self::CONFIG_ROOT . 'account_settings/sandbox_account_participations';

    // 400_checkout_presentation.xml
    const CONFIG_XML_PATH_EMULATED_CARRIER = self::CONFIG_ROOT . 'dhl_paket_checkout_settings/emulated_carrier';

    /**
     * Check whether the module is enabled or not.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function isEnabled($store = null): bool;

    /**
     * Get the sort order.
     *
     * @param string|null $store
     *
     * @return int
     */
    public function getSortOrder($store = null): int;

    /**
     * Get the title.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getTitle($store = null): string;

    /**
     * Get the emulated carrier.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getEmulatedCarrier($store = null): string;

    /**
     * Check if shipping only to specific countries.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function shipToSpecificCountries($store = null): bool;

    /**
     * Get the specific countries.
     *
     * @param string|null $store
     *
     * @return string[]
     */
    public function getSpecificCountries($store = null): array;

    /**
     * Get the error message.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getNotApplicableErrorMessage($store = null): string;

    /**
     * Get the logging status.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function isLoggingEnabled($store = null): bool;

    /**
     * Get the log level.
     *
     * @param string|null $store
     *
     * @return int
     */
    public function getLogLevel($store = null): int;

    /**
     * Get the authentication username.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getAuthUsername($store = null): string;

    /**
     * Get the authentication password.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getAuthPassword($store = null): string;

    /**
     * Get the API username.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getApiUsername($store = null): string;

    /**
     * Get the API password.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getApiPassword($store = null): string;

    /**
     * Get the account number.
     *
     * @param string|null $store
     *
     * @return string
     */
    public function getAccountNumber($store = null): string;

    /**
     * Returns the participation numbers.
     *
     * @param string|null $store
     *
     * @return string[]
     */
    public function getAccountParticipations($store = null): array;

    /**
     * Returns the participation number for a given procedure.
     *
     * @param string      $procedure
     * @param string|null $store
     *
     * @return string
     */
    public function getAccountParticipation(string $procedure, $store = null): string;

    /**
     * Returns true if sandbox mode is enabled.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function sandboxModeEnabled($store = null): bool;

    /**
     * Return true if sandbox mode is disabled.
     *
     * @param string|null $store
     *
     * @return bool
     */
    public function sandboxModeDisabled($store = null): bool;

    /**
     * Returns the EU countries list.
     *
     * @param mixed $store
     *
     * @return array
     */
    public function getEuCountryList($store = null): array;
}
