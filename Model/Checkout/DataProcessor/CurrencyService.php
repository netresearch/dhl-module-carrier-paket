<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Currency_Exception;

/**
 * Class CurrencyService
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Rico Sonntag <rico.sonntag@netresearch.de>
 */
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AdditionalChargesProcessor constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param CurrencyInterface $localeCurrency
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CurrencyInterface $localeCurrency,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->localeCurrency = $localeCurrency;
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
        $store        = $this->storeManager->getStore($storeId);
        $currencyCode = $store->getCurrentCurrencyCode();

        if (!$currencyCode) {
            $currencyCode = $store->getBaseCurrencyCode();
        }

        return $currencyCode;
    }

    /**
     * Returns a localized currency string.
     *
     * @param float    $amount  Amount to convert
     * @param int|null $storeId Store id
     *
     * @return string
     *
     * @throws Zend_Currency_Exception
     * @throws NoSuchEntityException
     */
    private function toCurrency(float $amount, int $storeId = null): string
    {
        return $this->localeCurrency
            ->getCurrency($this->getCurrencyCode($storeId))
            ->toCurrency($amount);
    }

    /**
     * @param float $amount
     * @param string $string
     * @param int|null $storeId
     *
     * @return string
     */
    public function replaceAmount(float $amount, string $string, int $storeId = null): string
    {
        try {
            // Translate the string now because later translation would fail.
            $translation = __($string)->render();

            return str_replace(
                '$1',
                $this->toCurrency($amount, $storeId),
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
