<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Unit\Model\ShippingSettings\TypeProcessor\ShippingOptions\Customs;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions\Customs\ExportNotificationInputsProcessor;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Model\ItemAttribute\ShipmentItemAttributeReader;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\Input;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\ShippingOption;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use PHPUnit\Framework\TestCase;

class ExportNotificationInputsProcessorTest extends TestCase
{
    /**
     * The export-notification checkbox default must follow the per-shipment customs value
     * (the sum of the shipment's items), not the whole-order subtotal.
     *
     * These cases are the acceptance criteria from DHLGW-1550 / GH netresearch/dhl-shipping-m2#84:
     * a partial shipment whose customs value is below 1000 EUR must default to unchecked even
     * when the order subtotal exceeds 1000 EUR.
     *
     * @dataProvider shipmentCustomsValueProvider
     */
    public function testDefaultsCheckboxByShipmentCustomsValue(float $shipmentCustomsValue, string $expectedDefault): void
    {
        $notificationInput = new Input();
        $notificationInput->setCode('electronicExportNotification');

        $customsOption = new ShippingOption();
        $customsOption->setCode(Codes::PACKAGE_OPTION_CUSTOMS);
        $customsOption->setInputs([$notificationInput]);

        $itemAttributeReader = $this->createMock(ShipmentItemAttributeReader::class);
        $itemAttributeReader->method('getTotalPrice')->willReturn($shipmentCustomsValue);

        $shipment = $this->createMock(ShipmentInterface::class);

        (new ExportNotificationInputsProcessor($itemAttributeReader))->process(
            Paket::CARRIER_CODE,
            [Codes::PACKAGE_OPTION_CUSTOMS => $customsOption],
            1,
            'US',
            '12345',
            $shipment
        );

        self::assertSame(
            $expectedDefault,
            $notificationInput->getDefaultValue(),
            sprintf(
                'Shipment customs value %.2f EUR must default the export-notification checkbox to "%s".',
                $shipmentCustomsValue,
                $expectedDefault
            )
        );
    }

    /**
     * @return array<string, array{0: float, 1: string}>
     */
    public static function shipmentCustomsValueProvider(): array
    {
        return [
            // GH#84 worked examples (per shipment / invoice)
            'A-1: 250 EUR shipment -> unchecked'  => [250.0, ''],
            'A-2: 1250 EUR shipment -> checked'   => [1250.0, '1'],
            'B-1/B-2: 750 EUR shipment -> unchecked' => [750.0, ''],
            'C-1: 1500 EUR shipment -> checked'   => [1500.0, '1'],
            // threshold boundary
            'exactly 1000 EUR -> checked'         => [1000.0, '1'],
            'just below 1000 EUR -> unchecked'    => [999.99, ''],
        ];
    }
}
