<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Unit\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions\PddpUsaProcessor;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\Input;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\ShippingOption;
use PHPUnit\Framework\TestCase;

/**
 * pDDP threshold cutover (US CBP change, DHL letter 2026-06-30): up to 2026-07-23 the DHL API
 * requires pDDP for US shipments <= 800 USD / 680 EUR and forbids it above; from 2026-07-24
 * (Europe/Berlin) the boundary is 2,500 USD / 2,125 EUR and Puerto Rico is covered as well.
 * The processor must mirror the API's server-side switch at runtime so one release behaves
 * correctly on both sides of the date (see DHLGW-1552 Umsetzungsansatz).
 */
class PddpUsaProcessorTest extends TestCase
{
    /** One second before 2026-07-24T00:00:00+02:00. */
    private const BEFORE_CUTOVER = 1784843999;

    /** Exactly 2026-07-24T00:00:00+02:00 as unix timestamp. */
    private const AT_CUTOVER = 1784844000;

    private Input $enabledInput;

    /**
     * @return array<string, ShippingOption>
     */
    private function pddpShippingOptions(): array
    {
        $this->enabledInput = new Input();
        $this->enabledInput->setCode('enabled');

        $pddpOption = new ShippingOption();
        $pddpOption->setCode(Codes::SERVICE_OPTION_PDDP);
        $pddpOption->setInputs([$this->enabledInput]);

        return [Codes::SERVICE_OPTION_PDDP => $pddpOption];
    }

    private function shipmentWithOrder(string $baseCurrency, float $baseSubtotal): Shipment
    {
        $order = $this->createMock(Order::class);
        $order->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $order->method('getBaseSubtotal')->willReturn($baseSubtotal);

        $shipment = $this->createMock(Shipment::class);
        $shipment->method('getOrder')->willReturn($order);

        return $shipment;
    }

    private function processorAt(int $timestamp): PddpUsaProcessor
    {
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn($timestamp);

        return new PddpUsaProcessor($dateTime);
    }

    /**
     * @dataProvider thresholdProvider
     */
    public function testThresholdSwitchesAtCutover(
        int $timestamp,
        string $countryCode,
        string $currency,
        float $subtotal,
        string $expectedDefault,
        bool $expectedDisabled
    ): void {
        $shippingOptions = $this->pddpShippingOptions();

        $this->processorAt($timestamp)->process(
            Paket::CARRIER_CODE,
            $shippingOptions,
            1,
            $countryCode,
            '12345',
            $this->shipmentWithOrder($currency, $subtotal)
        );

        self::assertSame($expectedDefault, $this->enabledInput->getDefaultValue());
        self::assertSame($expectedDisabled, $this->enabledInput->isDisabled());
        self::assertSame(
            $expectedDisabled,
            $this->enabledInput->isLocked(),
            'a processor disable is outside the rule set, so it must be locked against client-side re-enabling'
        );
    }

    /**
     * @return array<string, array{0: int, 1: string, 2: string, 3: float, 4: string, 5: bool}>
     */
    public static function thresholdProvider(): array
    {
        return [
            // legacy regime (before 2026-07-24 Europe/Berlin)
            'before: US 800 USD -> required'        => [self::BEFORE_CUTOVER, 'US', 'USD', 800.00, '1', false],
            'before: US 800.01 USD -> forbidden'    => [self::BEFORE_CUTOVER, 'US', 'USD', 800.01, '0', true],
            'before: US 680 EUR -> required'        => [self::BEFORE_CUTOVER, 'US', 'EUR', 680.00, '1', false],
            'before: US 680.01 EUR -> forbidden'    => [self::BEFORE_CUTOVER, 'US', 'EUR', 680.01, '0', true],
            // the transition band 800-2500 USD is the business-critical case
            'before: US 1200 USD -> forbidden'      => [self::BEFORE_CUTOVER, 'US', 'USD', 1200.00, '0', true],
            'from: US 1200 USD -> required'         => [self::AT_CUTOVER, 'US', 'USD', 1200.00, '1', false],
            // new regime (from 2026-07-24 Europe/Berlin)
            'from: US 2500 USD -> required'         => [self::AT_CUTOVER, 'US', 'USD', 2500.00, '1', false],
            'from: US 2500.01 USD -> forbidden'     => [self::AT_CUTOVER, 'US', 'USD', 2500.01, '0', true],
            'from: US 2125 EUR -> required'         => [self::AT_CUTOVER, 'US', 'EUR', 2125.00, '1', false],
            'from: US 2125.01 EUR -> forbidden'     => [self::AT_CUTOVER, 'US', 'EUR', 2125.01, '0', true],
            // Puerto Rico behaves like the USA in both regimes
            'before: PR 800 USD -> required'        => [self::BEFORE_CUTOVER, 'PR', 'USD', 800.00, '1', false],
            'from: PR 1200 USD -> required'         => [self::AT_CUTOVER, 'PR', 'USD', 1200.00, '1', false],
        ];
    }

    public function testTooltipNamesActiveThresholdAndCurrency(): void
    {
        $shippingOptions = $this->pddpShippingOptions();

        $this->processorAt(self::AT_CUTOVER)->process(
            Paket::CARRIER_CODE,
            $shippingOptions,
            1,
            'US',
            '12345',
            $this->shipmentWithOrder('USD', 100.00)
        );

        self::assertStringContainsString('2,500.00', (string) $this->enabledInput->getTooltip());
        self::assertStringContainsString('USD', (string) $this->enabledInput->getTooltip());
    }

    public function testUnsupportedCurrencyDisablesPddpRegardlessOfDate(): void
    {
        $shippingOptions = $this->pddpShippingOptions();

        $this->processorAt(self::AT_CUTOVER)->process(
            Paket::CARRIER_CODE,
            $shippingOptions,
            1,
            'US',
            '12345',
            $this->shipmentWithOrder('GBP', 100.00)
        );

        self::assertTrue($this->enabledInput->isDisabled());
        self::assertTrue($this->enabledInput->isLocked());
        self::assertStringContainsString('EUR or USD', (string) $this->enabledInput->getTooltip());
    }

    /**
     * @dataProvider untouchedProvider
     */
    public function testLeavesOptionUntouched(string $carrierCode, string $countryCode, bool $withShipment): void
    {
        $shippingOptions = $this->pddpShippingOptions();

        $this->processorAt(self::AT_CUTOVER)->process(
            $carrierCode,
            $shippingOptions,
            1,
            $countryCode,
            '12345',
            $withShipment ? $this->shipmentWithOrder('USD', 100.00) : null
        );

        self::assertSame('', $this->enabledInput->getDefaultValue());
        self::assertFalse($this->enabledInput->isDisabled());
        self::assertFalse($this->enabledInput->isLocked());
        self::assertSame('', (string) $this->enabledInput->getTooltip());
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function untouchedProvider(): array
    {
        return [
            'foreign carrier'                  => ['flatrate', 'US', true],
            'non-US destination (CH)'          => [Paket::CARRIER_CODE, 'CH', true],
            'checkout scope (no shipment)'     => [Paket::CARRIER_CODE, 'US', false],
        ];
    }
}
