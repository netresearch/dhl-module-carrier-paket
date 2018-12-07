<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Config;

interface ModuleConfigInterface
{
    const CONFIG_ROOT = 'carriers/dhlpaket/';

    const CONFIG_XML_PATH_ENABLED = self::CONFIG_ROOT . 'active';
    const CONFIG_XML_PATH_SORT_ORDER = self::CONFIG_ROOT . 'sort_order';
    const CONFIG_XML_PATH_TITLE = self::CONFIG_ROOT . 'title';
    const CONFIG_XML_PATH_EMULATED_CARRIER = self::CONFIG_ROOT . 'emulated_carrier';
    const CONFIG_XML_PATH_SHIP_TO_SPECIFIC_COUNTRIES = self::CONFIG_ROOT . 'sallowspecific';
    const CONFIG_XML_PATH_SPECIFIC_COUNTRIES = self::CONFIG_ROOT . 'specificcountry';
    const CONFIG_XML_PATH_ERROR_MESSAGE = self::CONFIG_ROOT . 'specificerrmsg';
    const CONFIG_XML_PATH_ENABLE_LOGGING = self::CONFIG_ROOT . 'logging';
    const CONFIG_XML_PATH_LOGLEVEL = self::CONFIG_ROOT . 'loglevel';

    /**
     * @param null $store
     * @return bool
     */
    public function isEnabled($store = null): bool;

    /**
     * Get the sort order.
     *
     * @param string|null $store
     * @return int
     */
    public function getSortOrder($store = null): int;

    /**
     * Get the title.
     *
     * @param string|null $store
     * @return string
     */
    public function getTitle($store = null): string;

    /**
     * Get the emulated carrier.
     *
     * @param string|null $store
     * @return string
     */
    public function getEmulatedCarrier($store = null): string;

    /**
     * Check if shipping only to specific countries.
     *
     * @param string|null $store
     * @return bool
     */
    public function shipToSpecificCountries($store = null): bool;

    /**
     * Get the specific countries.
     *
     * @param string|null $store
     * @return string[]
     */
    public function getSpecificCountries($store = null): array;

    /**
     * Get the error message.
     *
     * @param string|null $store
     * @return string
     */
    public function getNotApplicableErrorMessage($store = null): string;

    /**
     * Get the Logging status.
     *
     * @param string|null $store
     * @return bool
     */
    public function isLoggingEnabled($store = null): bool;

    /**
     * Get the log level.
     *
     * @param string|null $store
     * @return int
     */
    public function getLogLevel($store = null): int;
}
