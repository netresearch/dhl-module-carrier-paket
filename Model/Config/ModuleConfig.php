<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Shipping\Helper\Carrier;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ModuleConfig
 */
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
     * @param EncryptorInterface   $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor   = $encryptor;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
        return (string) $this->scopeConfig->getValue(
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
        return (bool) $this->scopeConfig->getValue(
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
        return (int) $this->scopeConfig->getValue(
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
    public function getApiUsername($store = null): string
    {
        if ($this->sandboxModeEnabled($store)) {
            return $this->getSandboxUsername($store);
        }

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
        if ($this->sandboxModeEnabled($store)) {
            return $this->getSandboxPassword($store);
        }

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
        if ($this->sandboxModeEnabled($store)) {
            return $this->getSandboxAccountNumber($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_API_ACCOUNT_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @inheritDoc
     */
    public function getAccountParticipations($store = null): array
    {
        if ($this->sandboxModeEnabled($store)) {
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
     * @inheritDoc
     */
    public function getAccountParticipation(string $procedure, $store = null): string
    {
        return $this->getAccountParticipations()[$procedure] ?? '';
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
     * @inheritDoc
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
