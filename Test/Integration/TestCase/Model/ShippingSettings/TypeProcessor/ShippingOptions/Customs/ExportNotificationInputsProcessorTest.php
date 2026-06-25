<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\ShippingSettings\TypeProcessor\ShippingOptions\Customs;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions\Customs\ExportNotificationInputsProcessor;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Convert\Order as OrderConverter;
use Magento\TestFramework\Helper\Bootstrap;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\Input;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\ShippingOption;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Test\Integration\Fixture\OrderBuilder;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Sales\OrderFixture;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;

/**
 * Reproduces DHLGW-1550 / GH netresearch/dhl-shipping-m2#84 with real records: an order whose
 * subtotal exceeds 1000 EUR is split into partial shipments, one below and one above the threshold.
 *
 * The export-notification checkbox default must follow each partial shipment's real customs value,
 * proving that ShipmentItemAttributeReader::getTotalPrice() yields the per-shipment total (not the
 * whole-order subtotal). Each partial shipment is built from the persisted order via the order
 * converter - exactly as the packaging popup builds the shipment it hands to this processor.
 *
 * @magentoAppIsolation enabled
 */
class ExportNotificationInputsProcessorTest extends TestCase
{
    private const SKU_BELOW_THRESHOLD = 'dhlgw1550-below-threshold';
    private const SKU_ABOVE_THRESHOLD = 'dhlgw1550-above-threshold';

    /**
     * @var OrderInterface|null
     */
    private static $order;

    /**
     * Order subtotal 1500 EUR: a 250 EUR item (below the threshold) and a 1250 EUR item (above it).
     *
     * @throws \Exception
     */
    public static function createOrderAboveThreshold(): void
    {
        // Default flatrate shipping (active out-of-box) keeps the fixture free of carrier config;
        // the processor receives the carrier code directly, so the order's method is irrelevant.
        self::$order = OrderBuilder::anOrder()
            ->withProducts(
                ProductBuilder::aSimpleProduct()->withSku(self::SKU_BELOW_THRESHOLD)->withPrice(250.0),
                ProductBuilder::aSimpleProduct()->withSku(self::SKU_ABOVE_THRESHOLD)->withPrice(1250.0)
            )->build();
    }

    /**
     * @throws \Exception
     */
    public static function createOrderAboveThresholdRollback(): void
    {
        if (self::$order instanceof OrderInterface) {
            OrderFixtureRollback::create()->execute(new OrderFixture(self::$order));
            self::$order = null;
        }
    }

    /**
     * @magentoDataFixture createOrderAboveThreshold
     */
    public function testDefaultsCheckboxByRealShipmentCustomsValue(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $order = $objectManager->get(OrderRepositoryInterface::class)->get((int) self::$order->getEntityId());
        $converter = $objectManager->create(OrderConverter::class);
        $processor = $objectManager->create(ExportNotificationInputsProcessor::class);

        $assertedSkus = [];
        foreach ($order->getAllItems() as $orderItem) {
            // one partial shipment per item: 250 EUR (below) and 1250 EUR (above)
            $shipment = $converter->toShipment($order);
            $shipment->addItem(
                $converter->itemToShipmentItem($orderItem)->setQty((float) $orderItem->getQtyOrdered())
            );

            $notificationInput = new Input();
            $notificationInput->setCode('electronicExportNotification');

            $customsOption = new ShippingOption();
            $customsOption->setCode(Codes::PACKAGE_OPTION_CUSTOMS);
            $customsOption->setInputs([$notificationInput]);

            $processor->process(
                Paket::CARRIER_CODE,
                [Codes::PACKAGE_OPTION_CUSTOMS => $customsOption],
                (int) $order->getStoreId(),
                'US',
                '12345',
                $shipment
            );

            $expectedDefault = $orderItem->getSku() === self::SKU_BELOW_THRESHOLD ? '' : '1';
            self::assertSame(
                $expectedDefault,
                $notificationInput->getDefaultValue(),
                sprintf(
                    'Partial shipment of SKU "%s" must default the export-notification checkbox to "%s".',
                    $orderItem->getSku(),
                    $expectedDefault
                )
            );
            $assertedSkus[] = $orderItem->getSku();
        }

        self::assertEqualsCanonicalizing(
            [self::SKU_BELOW_THRESHOLD, self::SKU_ABOVE_THRESHOLD],
            $assertedSkus,
            'Both the below-threshold and above-threshold partial shipments must be asserted.'
        );
    }
}
