<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Unit\Model\ShippingSettings\TypeProcessor\ItemShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\TypeProcessor\ItemShippingOptions\HsCodeTooltipProcessor;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\Input;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\ItemShippingOptions;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\ShippingOption;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use PHPUnit\Framework\TestCase;

/**
 * US/PR destinations get an HTSUS-specific help text with the official sources on the HS Code
 * input (merchants must determine the 10-digit code themselves — Deutsche Post provides no
 * lookup, see DHLGW-1555); all other destinations keep the generic tooltip configured in XML.
 */
class HsCodeTooltipProcessorTest extends TestCase
{
    private const GENERIC_TOOLTIP = '6 digits for shipments without export declaration, 8 digits with export declaration';

    private function hsCodeInput(string $presetTooltip): Input
    {
        $input = new Input();
        $input->setCode(Codes::ITEM_INPUT_HS_CODE);
        $input->setTooltip($presetTooltip);

        return $input;
    }

    /**
     * @return ItemShippingOptions[]
     */
    private function itemOptionsWith(Input $input): array
    {
        $customsOption = new ShippingOption();
        $customsOption->setCode(Codes::ITEM_OPTION_CUSTOMS);
        $customsOption->setInputs([$input]);

        $itemOptions = new ItemShippingOptions();
        $itemOptions->setShippingOptions([Codes::ITEM_OPTION_CUSTOMS => $customsOption]);

        return [$itemOptions];
    }

    /**
     * @dataProvider usTerritoryProvider
     */
    public function testUsTerritoryGetsHtsusHelpTextWithSources(string $countryCode): void
    {
        $input = $this->hsCodeInput(self::GENERIC_TOOLTIP);

        (new HsCodeTooltipProcessor())->process(
            Paket::CARRIER_CODE,
            $this->itemOptionsWith($input),
            1,
            $countryCode,
            '12345'
        );

        self::assertStringContainsString('10-digit', $input->getTooltip());
        self::assertStringContainsString('https://hts.usitc.gov/', $input->getTooltip());
        self::assertStringContainsString('https://www.dhl.de/us-versand', $input->getTooltip());
        self::assertStringNotContainsString('6 digits', $input->getTooltip());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function usTerritoryProvider(): array
    {
        return [
            'USA' => ['US'],
            'Puerto Rico' => ['PR'],
        ];
    }

    /**
     * @dataProvider otherDestinationProvider
     */
    public function testOtherDestinationsKeepGenericTooltip(string $countryCode): void
    {
        $input = $this->hsCodeInput(self::GENERIC_TOOLTIP);

        (new HsCodeTooltipProcessor())->process(
            Paket::CARRIER_CODE,
            $this->itemOptionsWith($input),
            1,
            $countryCode,
            '8000'
        );

        self::assertSame(self::GENERIC_TOOLTIP, $input->getTooltip());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function otherDestinationProvider(): array
    {
        return [
            'Switzerland' => ['CH'],
            'domestic' => ['DE'],
        ];
    }

    public function testItemsWithoutCustomsOptionAreSkipped(): void
    {
        $itemOptions = new ItemShippingOptions();
        $itemOptions->setShippingOptions([]);

        $result = (new HsCodeTooltipProcessor())->process(
            Paket::CARRIER_CODE,
            [$itemOptions],
            1,
            'US',
            '12345'
        );

        self::assertSame([$itemOptions], $result);
    }

    public function testNonHsCodeInputsAreUntouched(): void
    {
        $otherInput = new Input();
        $otherInput->setCode(Codes::ITEM_INPUT_CUSTOMS_VALUE);
        $otherInput->setTooltip('customs value tooltip');

        $customsOption = new ShippingOption();
        $customsOption->setCode(Codes::ITEM_OPTION_CUSTOMS);
        $customsOption->setInputs([$otherInput]);

        $itemOptions = new ItemShippingOptions();
        $itemOptions->setShippingOptions([Codes::ITEM_OPTION_CUSTOMS => $customsOption]);

        (new HsCodeTooltipProcessor())->process(Paket::CARRIER_CODE, [$itemOptions], 1, 'US', '12345');

        self::assertSame('customs value tooltip', $otherInput->getTooltip());
    }
}
