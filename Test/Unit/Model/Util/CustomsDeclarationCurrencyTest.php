<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Unit\Model\Util;

use Dhl\Paket\Model\Util\CustomsDeclarationCurrency;
use PHPUnit\Framework\TestCase;

/**
 * DHL requires customs values for US customs territory shipments (USA, Puerto Rico) to be
 * declared in USD, converted from EUR at DHL's fixed rate of 0.85 EUR/USD (DHL directive,
 * 2026-07-16, DHLGW-1561); USD base amounts need no conversion. Everything else keeps the
 * EUR label the SDK always used — the module targets the German market, other base
 * currencies are not supported.
 */
class CustomsDeclarationCurrencyTest extends TestCase
{
    private CustomsDeclarationCurrency $subject;

    protected function setUp(): void
    {
        $this->subject = new CustomsDeclarationCurrency();
    }

    /**
     * @dataProvider currencyProvider
     */
    public function testDeclarationCurrency(string $baseCurrency, string $destinationCountry, string $expected): void
    {
        self::assertSame($expected, $this->subject->getCurrency($baseCurrency, $destinationCountry));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function currencyProvider(): array
    {
        return [
            'EUR to USA declares USD'          => ['EUR', 'US', 'USD'],
            'EUR to Puerto Rico declares USD'  => ['EUR', 'PR', 'USD'],
            // the shipment request pipeline carries alpha-3 codes (Alpha3Converter in RequestExtractor)
            'EUR to USA (alpha-3) declares USD'         => ['EUR', 'USA', 'USD'],
            'EUR to Puerto Rico (alpha-3) declares USD' => ['EUR', 'PRI', 'USD'],
            'USD to USA declares USD'          => ['USD', 'US', 'USD'],
            'EUR to Switzerland stays EUR'     => ['EUR', 'CH', 'EUR'],
            // unsupported base currencies keep the EUR label (previous behavior, German market)
            'GBP to USA keeps EUR label'       => ['GBP', 'US', 'EUR'],
            'CHF to Norway keeps EUR label'    => ['CHF', 'NO', 'EUR'],
        ];
    }

    /**
     * @dataProvider valueProvider
     */
    public function testConvertValue(
        float $value,
        string $baseCurrency,
        string $destinationCountry,
        float $expected
    ): void {
        self::assertSame($expected, $this->subject->convert($value, $baseCurrency, $destinationCountry));
    }

    /**
     * @return array<string, array{0: float, 1: string, 2: string, 3: float}>
     */
    public static function valueProvider(): array
    {
        return [
            // EUR value / 0.85, rounded to two decimals
            'EUR to USA converts at fixed rate'      => [100.00, 'EUR', 'US', 117.65],
            'EUR to USA (alpha-3) converts'          => [100.00, 'EUR', 'USA', 117.65],
            'EUR to Puerto Rico converts'            => [29.99, 'EUR', 'PR', 35.28],
            // the pDDP boundary must map exactly: 2,125 EUR = 2,500 USD
            'EUR threshold maps to USD threshold'    => [2125.00, 'EUR', 'US', 2500.00],
            'just above EUR threshold stays above'   => [2125.01, 'EUR', 'US', 2500.01],
            'USD to USA is not converted'            => [800.00, 'USD', 'US', 800.00],
            'EUR to Switzerland is not converted'    => [100.00, 'EUR', 'CH', 100.00],
            'GBP to USA is not converted'            => [100.00, 'GBP', 'US', 100.00],
        ];
    }
}
