<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor\Metadata;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\Data\MetadataInterface;
use Dhl\ShippingCore\Model\Checkout\DataProcessor\MetadataProcessorInterface;
use Magento\Framework\CurrencyInterfaceFactory;
use Psr\Log\LoggerInterface;
use Zend_Currency_Exception;

/**
 * Class AdditionalChargesProcessor
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Max Melzer <max.melzer@netresearch.de>
 * @author Rico Sonntag <rico.sonntag@netresearch.de>
 */
class AdditionalChargesProcessor implements MetadataProcessorInterface
{
    /**
     * @var ModuleConfig
     */
    private $paketConfig;

    /**
     * @var CurrencyInterfaceFactory
     */
    private $currencyFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AdditionalChargesProcessor constructor.
     *
     * @param ModuleConfig $paketConfig
     * @param CurrencyInterfaceFactory $currencyFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleConfig $paketConfig,
        CurrencyInterfaceFactory $currencyFactory,
        LoggerInterface $logger
    ) {
        $this->paketConfig = $paketConfig;
        $this->currencyFactory = $currencyFactory;
        $this->logger = $logger;
    }

    /**
     * @param float $amount
     * @param string $string
     *
     * @return string
     */
    private function replaceAmount(float $amount, string $string): string
    {
        $result = '';

        try {
            // Translate the string now because later translation would fail.
            $string = __($string)->render();
            $currency = $this->currencyFactory->create();
            $result = str_replace('$1', $currency->toCurrency($amount), $string);
        } catch (Zend_Currency_Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    /**
     * @param MetadataInterface $metadata
     *
     * @return MetadataInterface
     */
    public function process(MetadataInterface $metadata): MetadataInterface
    {
        $footnote = $metadata->getFootnotes()['footnote-combined-cost'];

        if ($footnote) {
            $amount = $this->paketConfig->getPreferredCombinedCharge();

            if ($amount > 0.0) {
                $footnote->setContent(
                    $this->replaceAmount($amount, $footnote->getContent())
                );
            } else {
                // Remove Footnote
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
}
