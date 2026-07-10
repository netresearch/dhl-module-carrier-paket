<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Model\Util\UsCustomsTerritory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;

/**
 * Processor to handle PDDP (Postal Delivery Duty Paid) service pre-selection for USA/Puerto Rico shipments.
 *
 * DHL policy — the API enforces these thresholds server-side and switches on 2026-07-24 (Europe/Berlin):
 * - until 2026-07-23: shipments <= 800 USD / 680 EUR: PDDP mandatory; above: forbidden
 * - from 2026-07-24: shipments <= 2,500 USD / 2,125 EUR: PDDP mandatory; above: forbidden
 *
 * The processor mirrors the switch at runtime so a release deployed before the date behaves
 * correctly on both sides (see DHLGW-1552). The legacy pair is removed in a follow-up release.
 */
class PddpUsaProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * Thresholds for PDDP requirement until 2026-07-23 (DHL API enforced).
     */
    private const THRESHOLD_EUR_LEGACY = 680.00;
    private const THRESHOLD_USD_LEGACY = 800.00;

    /**
     * Thresholds for PDDP requirement from 2026-07-24 (DHL API enforced, rate 0.85 EUR/USD).
     */
    private const THRESHOLD_EUR_2026 = 2125.00;
    private const THRESHOLD_USD_2026 = 2500.00;

    /**
     * The instant the DHL API switches to the new thresholds (Europe/Berlin).
     */
    private const NEW_THRESHOLDS_EFFECTIVE_AT = '2026-07-24T00:00:00+02:00';

    public function __construct(private readonly DateTime $dateTime)
    {
    }

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
     * Get the PDDP threshold for the given currency, honoring the 2026-07-24 cutover.
     *
     * @param string $currencyCode The base currency code (EUR, USD, etc.).
     * @return float|null The threshold value, or null if currency not supported.
     */
    private function getThreshold(string $currencyCode): ?float
    {
        $newThresholdsEffective = $this->dateTime->gmtTimestamp() >= strtotime(self::NEW_THRESHOLDS_EFFECTIVE_AT);

        return match ($currencyCode) {
            'EUR' => $newThresholdsEffective ? self::THRESHOLD_EUR_2026 : self::THRESHOLD_EUR_LEGACY,
            'USD' => $newThresholdsEffective ? self::THRESHOLD_USD_2026 : self::THRESHOLD_USD_LEGACY,
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
            // Unsupported currency - disable PDDP entirely; lock it because the
            // client-side compatibility rule engine must not revert the disable
            $input->setDisabled(true);
            $input->setLocked(true);
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
                    'PDDP is REQUIRED for USA/Puerto Rico shipments with value <= %1 %2. The DHL API will reject shipments without PDDP enabled.',
                    number_format($threshold, 2),
                    $baseCurrency
                )
            );
        } else {
            // Above threshold - PDDP is forbidden, disable it; lock it because the
            // client-side compatibility rule engine must not revert the disable
            $input->setDefaultValue('0');
            $input->setDisabled(true);
            $input->setLocked(true);
            $input->setTooltip(
                (string) __(
                    'PDDP is NOT allowed for USA/Puerto Rico shipments with value > %1 %2. The DHL API will reject shipments with PDDP enabled.',
                    number_format($threshold, 2),
                    $baseCurrency
                )
            );
        }
    }

    /**
     * Process shipping options to configure PDDP for USA/Puerto Rico shipments.
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

        // Only process for shipments into the US customs territory (USA, Puerto Rico)
        if (!in_array($countryCode, UsCustomsTerritory::COUNTRY_CODES, true)) {
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
