<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Unit\Model\Pipeline\CreateShipments\ShipmentRequest\Validator;

use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Validator\HtsusCodeValidator;
use Magento\Framework\Exception\ValidatorException;
use Magento\Shipping\Model\Shipment\Request;
use PHPUnit\Framework\TestCase;

/**
 * Server-side backstop for the US CBP requirement (effective 2026-07-24): every item shipped to the
 * USA or Puerto Rico must carry a 10-digit HTSUS code. The DHL API does NOT reject shorter codes
 * (confirmed by DHL, see DHLGW-1556) — a non-compliant shipment fails at US import instead, so the
 * module must block label creation itself. Enforcement is intentionally NOT date-gated: DHL allows
 * switching tariff numbers before the deadline (see DHLGW-1552).
 */
class HtsusCodeValidatorTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private static function customsPackage(array $items): array
    {
        return [
            'params' => [
                'customs' => ['exportDescription' => 'test goods'],
            ],
            'items' => $items,
        ];
    }

    private static function request(string $countryCode, array ...$packages): Request
    {
        $request = new Request();
        $request->setData('recipient_address_country_code', $countryCode);
        $request->setData('packages', $packages);

        return $request;
    }

    /**
     * @dataProvider compliantRequestProvider
     */
    public function testAcceptsCompliantRequests(Request $request): void
    {
        (new HtsusCodeValidator())->validate($request);

        $this->addToAssertionCount(1); // reaching here means validation passed
    }

    /**
     * @return array<string, array{0: Request}>
     */
    public static function compliantRequestProvider(): array
    {
        return [
            'US item with 10-digit code' => [
                self::request('US', self::customsPackage([['customs' => ['hsCode' => '6109909000']]])),
            ],
            'PR item with 10-digit code' => [
                self::request('PR', self::customsPackage([['customs' => ['hsCode' => '4901990000']]])),
            ],
            'non-US destination keeps 6-digit code' => [
                self::request('CH', self::customsPackage([['customs' => ['hsCode' => '610990']]])),
            ],
            'US package without customs data is skipped' => [
                self::request('US', ['params' => ['customs' => []], 'items' => [['customs' => ['hsCode' => '610990']]]]),
            ],
        ];
    }

    /**
     * @dataProvider nonCompliantCodeProvider
     */
    public function testRejectsNonTenDigitCodesForUsTerritory(string $countryCode, string $hsCode): void
    {
        $request = $this->request($countryCode, $this->customsPackage([['customs' => ['hsCode' => $hsCode]]]));

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('10-digit HTSUS');

        (new HtsusCodeValidator())->validate($request);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function nonCompliantCodeProvider(): array
    {
        return [
            'US 6-digit code'        => ['US', '610990'],
            'US 8-digit code'        => ['US', '61099090'],
            'US missing code'        => ['US', ''],
            'US 11-digit code'       => ['US', '61099090001'],
            'US non-numeric code'    => ['US', '6109A0900B'],
            'PR 8-digit code'        => ['PR', '61099090'],
        ];
    }

    /**
     * A multi-item package is only compliant when EVERY item carries a 10-digit code.
     */
    public function testRejectsWhenOneOfSeveralItemsIsNonCompliant(): void
    {
        $request = $this->request('US', $this->customsPackage([
            ['customs' => ['hsCode' => '6109909000']],
            ['customs' => ['hsCode' => '610990']],
        ]));

        $this->expectException(ValidatorException::class);

        (new HtsusCodeValidator())->validate($request);
    }
}
