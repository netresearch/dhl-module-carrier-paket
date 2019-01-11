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
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var EncryptorInterface
     */
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getLogLevel($store = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_LOGLEVEL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getAuthUsername($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_AUTH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getAuthPassword($store = null): string
    {
        return (string) $this->encryptor->decrypt(
            $this->scopeConfig->getValue(
                self::CONFIG_XML_PATH_AUTH_PASSWORD,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getApiUsername($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getApiPassword($store = null): string
    {
        return (string) $this->encryptor->decrypt(
            $this->scopeConfig->getValue(
                self::CONFIG_XML_PATH_API_PASSWORD,
                ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getAccountNumber($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_ACCOUNT_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function sandboxModeEnabled($store = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_SANDBOX_MODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function sandboxModeDisabled($store = null): bool
    {
        return !$this->sandboxModeEnabled($store);
    }

    /**
     * @inheritDoc
     */
    public function getProduct($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_PRODUCT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
