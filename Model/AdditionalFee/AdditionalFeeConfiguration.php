<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\AdditionalFee;

use Dhl\Paket\Model\Carrier\Paket;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;
use Netresearch\ShippingCore\Api\AdditionalFee\AdditionalFeeConfigurationInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionManager;

class AdditionalFeeConfiguration implements AdditionalFeeConfigurationInterface
{
    /**
     * @var QuoteSelectionManager
     */
    private $quoteSelectionManager;

    /**
     * @var AdditionalFeeProvider
     */
    private $feeProvider;

    /**
     * @var float
     */
    private $serviceAdjustment;

    public function __construct(
        QuoteSelectionManager $quoteSelectionManager,
        AdditionalFeeProvider $feeProvider
    ) {
        $this->quoteSelectionManager = $quoteSelectionManager;
        $this->feeProvider = $feeProvider;
    }

    private function calculateAdjustmentAmount(Quote $quote): float
    {
        if (!is_float($this->serviceAdjustment)) {
            $fees = $this->feeProvider->getAmounts((int) $quote->getStoreId());

            $selections = $this->quoteSelectionManager->load((int) $quote->getShippingAddress()->getId());
            $serviceCodes = array_unique(
                array_map(
                    static function (SelectionInterface $selection) {
                        return $selection->getShippingOptionCode();
                    },
                    $selections
                )
            );

            $serviceAdjustment = 0;
            foreach ($serviceCodes as $serviceCode) {
                $serviceAdjustment += $fees[$serviceCode] ?? 0;
            }

            $this->serviceAdjustment = round($serviceAdjustment, 2);
        }

        return $this->serviceAdjustment;
    }

    /**
     * @return string
     */
    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }

    /**
     * @param Quote $quote
     * @return bool
     */
    public function isActive(Quote $quote): bool
    {
        $adjustment = $this->calculateAdjustmentAmount($quote);
        return !empty($adjustment);
    }

    /**
     * @param Quote $quote
     * @return float
     */
    public function getServiceCharge(Quote $quote): float
    {
        return $this->calculateAdjustmentAmount($quote);
    }

    /**
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __('DHL Shipping Service Adjustment');
    }
}
