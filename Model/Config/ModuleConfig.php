<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class ModuleConfig implements ModuleConfigInterface
{
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var EncryptorInterface */
    private $encryptor;

    /**
     * ModuleConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the sort order.
     *
     * @param string|null $store
     * @return int
     */
    public function getSortOrder($store = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_SORT_ORDER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the title.
     *
     * @param string|null $store
     * @return string
     */
    public function getTitle($store = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_TITLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the emulated carrier.
     *
     * @param string|null $store
     * @return string
     */
    public function getEmulatedCarrier($store = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_EMULATED_CARRIER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if shipping only to specific countries.
     *
     * @param string|null $store
     * @return bool
     */
    public function shipToSpecificCountries($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_SHIP_TO_SPECIFIC_COUNTRIES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the specific countries.
     *
     * @param string|null $store
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
     * Get the error message.
     *
     * @param string|null $store
     * @return string
     */
    public function getNotApplicableErrorMessage($store = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_ERROR_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the Logging status.
     *
     * @param string|null $store
     * @return bool
     */
    public function isLoggingEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_ENABLE_LOGGING,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the log level.
     *
     * @param string|null $store
     * @return int
     */
    public function getLogLevel($store = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_LOGLEVEL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
