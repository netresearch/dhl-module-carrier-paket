<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Checkout;

use Dhl\ShippingCore\Api\Util\UnitConverterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Currency_Exception;

class CurrencyService
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CurrencyInterface
     */
    protected $localeCurrency;

    /**
     * @var UnitConverterInterface
     */
    private $unitConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CurrencyService constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param CurrencyInterface $localeCurrency
     * @param UnitConverterInterface $unitConverter
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CurrencyInterface $localeCurrency,
        UnitConverterInterface $unitConverter,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->localeCurrency = $localeCurrency;
        $this->unitConverter = $unitConverter;
        $this->logger = $logger;
    }

    /**
     * Get store current currency code.
     *
     * @param int|null $storeId
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getCurrencyCode(int $storeId = null): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore($storeId);

        return $store->getCurrentCurrencyCode() ?: $this->getBaseCurrencyCode();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getBaseCurrencyCode(): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return $store->getBaseCurrencyCode();
    }

    /**
     * Returns a localized and converted currency string.
     *
     * @param float $baseAmount Amount in base currency to convert
     * @param int|null $storeId Store id
     *
     * @return string Formatted string for amount in store (display) currency
     *
     * @throws Zend_Currency_Exception
     * @throws NoSuchEntityException
     */
    private function toCurrency(float $baseAmount, int $storeId = null): string
    {
        $storeCurrencyCode = $this->getCurrencyCode($storeId);
        $displayAmount = $this->unitConverter->convertMonetaryValue(
            $baseAmount,
            $this->getBaseCurrencyCode(),
            $storeCurrencyCode
        );
        return $this->localeCurrency
            ->getCurrency($storeCurrencyCode)
            ->toCurrency($displayAmount);
    }

    /**
     * @param float $baseAmount
     * @param string $string
     * @param int|null $storeId
     *
     * @return string
     */
    public function replaceAmount(float $baseAmount, string $string, int $storeId = null): string
    {
        try {
            // Translate the string now because later translation would fail.
            $translation = __($string)->render();

            return str_replace(
                '$1',
                $this->toCurrency($baseAmount, $storeId),
                $translation
            );
        } catch (Zend_Currency_Exception $e) {
            $this->logger->error($e->getMessage());
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
        }

        return '';
    }
}
