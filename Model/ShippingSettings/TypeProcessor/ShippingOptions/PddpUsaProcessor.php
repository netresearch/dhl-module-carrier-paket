<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;

/**
 * Processor to handle PDDP (Postal Delivery Duty Paid) service pre-selection for USA shipments.
 *
 * DHL Policy (effective Sept 25, 2025):
 * - USA shipments <= 800 USD / 680 EUR: PDDP mandatory
 * - USA shipments > 800 USD / 680 EUR: PDDP forbidden
 *
 * The DHL API enforces these thresholds directly.
 */
class PddpUsaProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * EUR threshold for PDDP requirement (DHL API enforced).
     */
    private const THRESHOLD_EUR = 680.00;

    /**
     * USD threshold for PDDP requirement (DHL API enforced).
     */
    private const THRESHOLD_USD = 800.00;

    /**
     * Destination country code for USA.
     */
    private const COUNTRY_USA = 'US';

    /**
     * Retrieves the 'enabled' input from the PDDP service option.
     *
     * @param ShippingOptionInterface $serviceOption The PDDP shipping service option.
     * @return InputInterface|null The enabled input, or null if not found.
     */
    private function getEnabledInput(ShippingOptionInterface $serviceOption): ?InputInterface
    {
        foreach ($serviceOption->getInputs() as $input) {
            if ($input->getCode() === 'enabled') {
                return $input;
            }
        }

        return null;
    }

    /**
     * Get the PDDP threshold for the given currency.
     *
     * @param string $currencyCode The base currency code (EUR, USD, etc.).
     * @return float|null The threshold value, or null if currency not supported.
     */
    private function getThreshold(string $currencyCode): ?float
    {
        return match ($currencyCode) {
            'EUR' => self::THRESHOLD_EUR,
            'USD' => self::THRESHOLD_USD,
            default => null,
        };
    }

    /**
     * Configure the PDDP input based on shipment value and currency.
     *
     * @param InputInterface $input The PDDP enabled input.
     * @param float $orderSubtotal The order subtotal in base currency.
     * @param string $baseCurrency The store's base currency code.
     * @return void
     */
    private function configurePddpInput(
        InputInterface $input,
        float $orderSubtotal,
        string $baseCurrency
    ): void {
        $threshold = $this->getThreshold($baseCurrency);

        if ($threshold === null) {
            // Unsupported currency - disable PDDP entirely
            $input->setDisabled(true);
            $input->setTooltip(
                (string) __('PDDP is only available for stores with EUR or USD as base currency.')
            );
            return;
        }

        if ($orderSubtotal <= $threshold) {
            // Below/at threshold - PDDP is required, pre-select it
            $input->setDefaultValue('1');
            $input->setTooltip(
                (string) __(
                    'PDDP is REQUIRED for USA shipments with value <= %1 %2. The DHL API will reject shipments without PDDP enabled.',
                    number_format($threshold, 2),
                    $baseCurrency
                )
            );
        } else {
            // Above threshold - PDDP is forbidden, disable it
            $input->setDefaultValue('0');
            $input->setDisabled(true);
            $input->setTooltip(
                (string) __(
                    'PDDP is NOT allowed for USA shipments with value > %1 %2. The DHL API will reject shipments with PDDP enabled.',
                    number_format($threshold, 2),
                    $baseCurrency
                )
            );
        }
    }

    /**
     * Process shipping options to configure PDDP for USA shipments.
     *
     * @param string $carrierCode The carrier code to be checked.
     * @param array $shippingOptions The available shipping options to be processed.
     * @param int $storeId The store ID context for the shipping options.
     * @param string $countryCode The destination country code.
     * @param string $postalCode The destination postal code.
     * @param ShipmentInterface|null $shipment The shipment instance, or null if in checkout scope.
     * @return array The modified or original array of shipping options.
     */
    #[\Override]
    public function process(
        string $carrierCode,
        array $shippingOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ?ShipmentInterface $shipment = null
    ): array {
        // Only process for DHL Paket carrier
        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $shippingOptions;
        }

        // Only process in admin/packaging scope (not checkout)
        if (!$shipment) {
            return $shippingOptions;
        }

        // Only process for USA shipments
        if ($countryCode !== self::COUNTRY_USA) {
            return $shippingOptions;
        }

        // Get PDDP service option
        $pddpOption = $shippingOptions[Codes::SERVICE_OPTION_PDDP] ?? null;
        if (!$pddpOption instanceof ShippingOptionInterface) {
            return $shippingOptions;
        }

        // Get the enabled input
        $enabledInput = $this->getEnabledInput($pddpOption);
        if (!$enabledInput instanceof InputInterface) {
            return $shippingOptions;
        }

        // Get order data
        $order = $shipment->getOrder();
        $baseCurrency = (string) $order->getBaseCurrencyCode();
        $orderSubtotal = (float) $order->getBaseSubtotal();

        // Configure the PDDP input based on value and currency
        $this->configurePddpInput($enabledInput, $orderSubtotal, $baseCurrency);

        return $shippingOptions;
    }
}
