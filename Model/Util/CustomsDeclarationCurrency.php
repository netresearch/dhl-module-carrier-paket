<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Util;

/**
 * Currency for customs declarations towards the DHL API.
 *
 * DHL requires shipments into the US customs territory (USA, Puerto Rico) to declare
 * customs values in USD, converted from EUR at DHL's fixed rate of 0.85 EUR/USD
 * (DHL directive, 2026-07-16, DHLGW-1561). Declaring in USD makes module, DHL, and CBP
 * evaluate the same number, eliminating the exchange rate band at the pDDP threshold.
 * USD base amounts are declared as-is.
 *
 * All other combinations keep the EUR label the SDK always used — the module targets
 * the German market, base currencies other than EUR and USD are not supported.
 */
class CustomsDeclarationCurrency
{
    /**
     * DHL's fixed exchange rate. The pDDP thresholds are derived from it: 2,125 EUR = 2,500 USD x 0.85.
     */
    public const EUR_PER_USD = 0.85;

    private const EUR = 'EUR';
    private const USD = 'USD';

    /**
     * Get the currency code the customs values must be declared in.
     *
     * @param string $baseCurrency The store's base currency code.
     * @param string $destinationCountry The destination country code (ISO 3166-1 alpha-2 or alpha-3).
     */
    public function getCurrency(string $baseCurrency, string $destinationCountry): string
    {
        if ($baseCurrency === self::USD && $this->isUsCustomsTerritory($destinationCountry)) {
            return self::USD;
        }

        if ($this->requiresConversion($baseCurrency, $destinationCountry)) {
            return self::USD;
        }

        return self::EUR;
    }

    /**
     * Convert a base currency amount to the declaration currency.
     *
     * @param float $value Amount in the store's base currency.
     * @param string $baseCurrency The store's base currency code.
     * @param string $destinationCountry The destination country code (ISO 3166-1 alpha-2 or alpha-3).
     */
    public function convert(float $value, string $baseCurrency, string $destinationCountry): float
    {
        if ($this->requiresConversion($baseCurrency, $destinationCountry)) {
            return round($value / self::EUR_PER_USD, 2);
        }

        return $value;
    }

    private function requiresConversion(string $baseCurrency, string $destinationCountry): bool
    {
        return $baseCurrency === self::EUR && $this->isUsCustomsTerritory($destinationCountry);
    }

    private function isUsCustomsTerritory(string $countryCode): bool
    {
        $usCustomsTerritory = array_merge(
            UsCustomsTerritory::COUNTRY_CODES,
            UsCustomsTerritory::COUNTRY_CODES_ALPHA3
        );

        return in_array($countryCode, $usCustomsTerritory, true);
    }
}
