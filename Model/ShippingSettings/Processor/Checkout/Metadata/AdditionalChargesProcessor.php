<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Checkout\Metadata;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingSettings\Processor\Checkout\CurrencyService;
use Dhl\ShippingCore\Api\Data\ShippingSettings\MetadataInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Checkout\MetadataProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AdditionalChargesProcessor
 *
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
