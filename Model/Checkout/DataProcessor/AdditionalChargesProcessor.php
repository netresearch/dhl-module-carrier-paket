<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ProcessorInterface;
use Dhl\ShippingCore\Api\Data\FootnoteInterface;
use Dhl\ShippingCore\Api\Data\MetadataInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\InputInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\AbstractProcessor;
use Magento\Framework\CurrencyInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AdditionalChargesProcessor
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Sebastian Ertner <sebastian.ertner@netresearch.de>
 */
class AdditionalChargesProcessor extends AbstractProcessor
{
    /**
     * @var ModuleConfig
     */
    private $paketConfig;

    /**
     * @var CurrencyInterface
     */
    private $currency;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AdditionalChargesProcessor constructor.
     *
     * @param ModuleConfig $paketConfig
     * @param CurrencyInterface $currency
     * @param LoggerInterface $logger
     */
    public function __construct(ModuleConfig $paketConfig, CurrencyInterface $currency, LoggerInterface $logger)
    {
        $this->paketConfig = $paketConfig;
        $this->currency = $currency;
        $this->logger = $logger;
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param string $countryId
     * @param string $postalCode
     * @param int|null $scopeId
     *
     * @return ShippingOptionInterface[]
     */
    public function processShippingOptions(
        array $optionsData,
        string $countryId,
        string $postalCode,
        int $scopeId = null
    ): array {
        $date = $optionsData[ProcessorInterface::CHECKOUT_SERVICE_PREFERRED_DAY]->getInputs()['date'] ?? false;
        if ($date) {
            $amount = $this->paketConfig->getPreferredDayAdditionalCharge();
            $this->apply($amount, $date);
        }
        $time = $optionsData[ProcessorInterface::CHECKOUT_SERVICE_PREFERRED_TIME]->getInputs()['time'] ?? false;
        if ($time) {
            $amount = $this->paketConfig->getPreferredTimeAdditionalCharge();
            $this->apply($amount, $time);
        }

        return $optionsData;
    }

    /**
     *
     * @param MetadataInterface $metadata
     * @param string $countryId
     * @param string $postalCode
     * @param int|null $scopeId
     * @return MetadataInterface
     */
    public function processMetadata(
        MetadataInterface $metadata,
        string $countryId,
        string $postalCode,
        int $scopeId = null
    ): MetadataInterface {
        $footnote = $metadata->getFootnotes()['footnote-combined-cost'];
        if ($footnote) {
            $amount = $this->paketConfig->getPreferredCombinedCharge();
            if ($amount > 0.0) {
                $footnote->setContent(
                    $this->replaceAmount($amount, $footnote->getContent())
                );
            } else {
                /** Remove Footnote */
                $metadata->setFootnotes(array_filter(
                    $metadata->getFootnotes(),
                    static function ($footnote) {
                        return $footnote !== 'footnote-combined-cost';
                    }
                ));
            }
        }

        return $metadata;
    }

    /**
     * Apply the amount to the input comment or remove the comment if the amount is 0.
     *
     * @param float $amount
     * @param InputInterface $date
     */
    private function apply(float $amount, InputInterface $date): void
    {
        $comment = $date->getComment();
        if (!$comment) {
            return;
        }
        if ($amount > 0.0) {
            $comment->setContent(
                $this->replaceAmount($amount, $comment->getContent())
            );
        } else {
            $date->setComment(null);
        }
    }

    /**
     * @param float $amount
     * @param string $string
     * @return string
     */
    private function replaceAmount(float $amount, string $string): string
    {
        $result = '';
        try {
            /** Translate the string now because later translation would fail. */
            $string = __($string)->render();
            $result = str_replace('$1', $this->currency->toCurrency($amount), $string);
        } catch (\Zend_Currency_Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
