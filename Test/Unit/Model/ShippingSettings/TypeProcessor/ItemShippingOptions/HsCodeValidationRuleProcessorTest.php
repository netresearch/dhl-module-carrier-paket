<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Unit\Model\ShippingSettings\TypeProcessor\ItemShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\TypeProcessor\ItemShippingOptions\HsCodeValidationRuleProcessor;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\ValidationRuleInterfaceFactory;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\Input;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\ItemShippingOptions;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\ShippingOption;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\ValidationRule;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use PHPUnit\Framework\TestCase;

/**
 * Client-side half of the US CBP requirement (see HtsusCodeValidatorTest for the server-side half):
 * the packaging popup's hsCode input must demand exactly 10 digits for US/PR destinations, while the
 * pre-existing CH required-rule and the behavior for all other destinations stay untouched.
 */
class HsCodeValidationRuleProcessorTest extends TestCase
{
    private function hsCodeInput(): Input
    {
        $input = new Input();
        $input->setCode(Codes::ITEM_INPUT_HS_CODE);

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

    private function processor(): HsCodeValidationRuleProcessor
    {
        $factory = $this->createMock(ValidationRuleInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(static fn (): ValidationRule => new ValidationRule());

        return new HsCodeValidationRuleProcessor($factory);
    }

    /**
     * @dataProvider usTerritoryProvider
     */
    public function testUsTerritoryEnforcesExactlyTenDigits(string $countryCode): void
    {
        $input = $this->hsCodeInput();

        $this->processor()->process(Paket::CARRIER_CODE, $this->itemOptionsWith($input), 1, $countryCode, '12345');

        $rules = $input->getValidationRules();
        self::assertArrayHasKey('required', $rules);
        self::assertArrayHasKey('minLength', $rules);
        self::assertSame(10, $rules['minLength']->getParam());
        self::assertArrayHasKey('maxLength', $rules);
        self::assertSame(10, $rules['maxLength']->getParam());
        self::assertArrayHasKey('validate-number', $rules);
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

    public function testSwitzerlandKeepsRequiredRuleOnly(): void
    {
        $input = $this->hsCodeInput();

        $this->processor()->process(Paket::CARRIER_CODE, $this->itemOptionsWith($input), 1, 'CH', '8000');

        $rules = $input->getValidationRules();
        self::assertArrayHasKey('required', $rules);
        self::assertArrayNotHasKey('minLength', $rules);
        self::assertArrayNotHasKey('maxLength', $rules);
        self::assertArrayNotHasKey('validate-number', $rules);
    }

    public function testOtherDestinationsGetNoRules(): void
    {
        $input = $this->hsCodeInput();

        $this->processor()->process(Paket::CARRIER_CODE, $this->itemOptionsWith($input), 1, 'DE', '04229');

        self::assertSame([], $input->getValidationRules());
    }

    /**
     * Rules configured elsewhere (e.g. via shipping_settings.xml) must survive the merge.
     */
    public function testPreexistingRulesArePreserved(): void
    {
        $preexisting = new ValidationRule();
        $preexisting->setName('validate-no-html-tags');

        $input = $this->hsCodeInput();
        $input->setValidationRules(['validate-no-html-tags' => $preexisting]);

        $this->processor()->process(Paket::CARRIER_CODE, $this->itemOptionsWith($input), 1, 'US', '12345');

        $rules = $input->getValidationRules();
        self::assertArrayHasKey('validate-no-html-tags', $rules);
        self::assertArrayHasKey('required', $rules);
    }

    public function testItemsWithoutCustomsOptionAreSkipped(): void
    {
        $itemOptions = new ItemShippingOptions();
        $itemOptions->setShippingOptions([]);

        $result = $this->processor()->process(Paket::CARRIER_CODE, [$itemOptions], 1, 'US', '12345');

        self::assertSame([$itemOptions], $result);
    }
}
