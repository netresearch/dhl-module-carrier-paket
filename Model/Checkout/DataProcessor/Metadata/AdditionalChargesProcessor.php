<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor\Metadata;

use Dhl\Paket\Model\Checkout\DataProcessor\CurrencyService;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\Data\MetadataInterface;
use Dhl\ShippingCore\Model\Checkout\DataProcessor\MetadataProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var CurrencyService
     */
    protected $currencyService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AdditionalChargesProcessor constructor.
     *
     * @param ModuleConfig $paketConfig
     * @param CurrencyService $currencyService
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleConfig $paketConfig,
        CurrencyService $currencyService,
        LoggerInterface $logger
    ) {
        $this->paketConfig = $paketConfig;
        $this->currencyService = $currencyService;
        $this->logger = $logger;
    }

    /**
     * @param MetadataInterface $metadata
     * @param int|null $storeId
     *
     * @return MetadataInterface
     */
    public function process(MetadataInterface $metadata, int $storeId = null): MetadataInterface
    {
        $footnote = $metadata->getFootnotes()['footnote-combined-cost'];

        if ($footnote) {
            $amount = $this->paketConfig->getPreferredCombinedCharge();

            if ($amount > 0.0) {
                $footnote->setContent(
                    $this->currencyService->replaceAmount($amount, $footnote->getContent(), $storeId)
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
