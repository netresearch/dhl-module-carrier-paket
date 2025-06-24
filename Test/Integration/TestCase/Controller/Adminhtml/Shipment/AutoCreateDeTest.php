<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Shipment;

use Dhl\Paket\Model\Pipeline\CreateShipments\Stage\SendRequestStage;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Netresearch\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Netresearch\ShippingCore\Test\Integration\Fixture\OrderBuilder;
use TddWizard\Fixtures\Sales\OrderFixture;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;
use TddWizard\Fixtures\Sales\ShipmentBuilder;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class AutoCreateDeTest extends AbstractAutoCreate
{
    /**
     * @var OrderInterface[]|Order[]
     */
    private static $orders;

    /**
     * @var OrderInterface[]|Order[]
     */
    private static $shippedOrders;

    /**
     * Create order fixtures for DE recipient address.
     *
     * - three orders with no shipment
     * - three orders with shipment but label status failed (no label, no track)
     *
     * @throws \Exception
     */
    public static function createOrders()
    {
        self::$orders = [];
        self::$shippedOrders = [];

        for ($i = 0; $i < 3; $i++) {
            $order = OrderBuilder::anOrder()->withShippingMethod('dhlpaket_flatrate')->build();
            $shippedOrder = OrderBuilder::anOrder()
                ->withShippingMethod('dhlpaket_flatrate')
                ->withLabelStatus(LabelStatusManagementInterface::LABEL_STATUS_FAILED)
                ->build();
            ShipmentBuilder::forOrder($shippedOrder)->build();

            self::$orders[] = $order;
            self::$shippedOrders[] = $shippedOrder;
        }
    }

    /**
     * @throws \Exception
     */
    public static function createOrdersRollback()
    {
        $orderFixtures = array_map(
            function (OrderInterface $order) {
                return new OrderFixture($order);
            },
            array_merge(self::$orders, self::$shippedOrders)
        );

        try {
            OrderFixtureRollback::create()->execute(...$orderFixtures);
        } catch (\Exception $exception) {
            $argv = $_SERVER['argv'] ?? [];
            if (in_array('--verbose', $argv, true)) {
                $message = sprintf("Error during rollback: %s%s", $exception->getMessage(), PHP_EOL);
                register_shutdown_function('fwrite', STDERR, $message);
            }
        }
    }

    /**
     * @magentoDataFixture createOrders
     *
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 91
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 04229
     * @magentoConfigFixture default_store general/store_information/city Leipzig
     * @magentoConfigFixture default_store general/store_information/street_line1 Nonnenstraße 11
     * @magentoConfigFixture default_store trans_email/ident_general/email admin@example.com
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 91
     * @magentoConfigFixture default_store shipping/origin/postcode 04229
     * @magentoConfigFixture default_store shipping/origin/city Leipzig
     * @magentoConfigFixture default_store shipping/origin/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture default_store catalog/price/scope 0
     * @magentoConfigFixture default_store currency/options/base EUR
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/retry_failed_shipments 0
     */
    #[\Override]
    #[\PHPUnit\Framework\Attributes\Test]
    public function createLabels()
    {
        $selectedPendingOrderIds = [
            self::$orders[0]->getId(),
            self::$orders[2]->getId(),
        ];
        $selectedProcessedOrderIds = [
            self::$shippedOrders[0]->getId(),
            self::$shippedOrders[2]->getId(),
        ];
        $selectedOrderIds = array_merge($selectedPendingOrderIds, $selectedProcessedOrderIds);

        // prepare mass action post data from order fixtures
        $postData = [
            'selected' => $selectedOrderIds,
            'namespace' => 'sales_order_grid'
        ];

        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue($postData);
        $this->dispatch($this->uri);

        /** @var SendRequestStageStub $pipelineStage */
        $pipelineStage = $this->_objectManager->get(SendRequestStage::class);

        // assert only pending orders were sent to api
        self::assertCount(count($selectedPendingOrderIds), $pipelineStage->apiRequests);

        // load shipments for all orders placed during test setup
        $fixtureOrderIds = array_map(function (Order $order) {
            return $order->getId();
        }, array_merge(self::$orders, self::$shippedOrders));

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipmentCollection->addFieldToFilter('order_id', ['in' => [$fixtureOrderIds]]);

        // assert every order now has one shipment
        $shipmentCount = count(self::$shippedOrders) + count($selectedPendingOrderIds);
        self::assertCount($shipmentCount, $shipmentCollection);

        /** @var ShipmentInterface $shipment */
        foreach ($shipmentCollection as $shipment) {
            /** @var ShipmentTrackInterface[] $tracks */
            $tracks = array_values($shipment->getTracks());
            if (in_array($shipment->getOrderId(), $selectedPendingOrderIds)) {
                // requested orders should now have exactly one label and one track assigned
                self::assertStringStartsWith('%PDF-1', $shipment->getShippingLabel());
                self::assertCount(1, $tracks);
                self::assertStringStartsWith((string)$shipment->getOrderId(), $tracks[0]->getTrackNumber());
            } else {
                // existing orders should remain untouched
                self::assertEmpty($shipment->getShippingLabel());
                self::assertEmpty($tracks);
            }
        }
    }

    /**
     * @magentoDataFixture createOrders
     *
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 91
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 04229
     * @magentoConfigFixture default_store general/store_information/city Leipzig
     * @magentoConfigFixture default_store general/store_information/street_line1 Nonnenstraße 11
     * @magentoConfigFixture default_store trans_email/ident_general/email admin@example.com
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 91
     * @magentoConfigFixture default_store shipping/origin/postcode 04229
     * @magentoConfigFixture default_store shipping/origin/city Leipzig
     * @magentoConfigFixture default_store shipping/origin/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture default_store catalog/price/scope 0
     * @magentoConfigFixture default_store currency/options/base EUR
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/retry_failed_shipments 1
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function createLabelsWithRetryEnabled()
    {
        $selectedPendingOrderIds = [
            self::$orders[0]->getId(),
            self::$orders[2]->getId(),
        ];
        $selectedProcessedOrderIds = [
            self::$shippedOrders[0]->getId(),
            self::$shippedOrders[2]->getId(),
        ];
        $selectedOrderIds = array_merge($selectedPendingOrderIds, $selectedProcessedOrderIds);

        // prepare mass action post data from order fixtures
        $postData = [
            'selected' => $selectedOrderIds,
            'namespace' => 'sales_order_grid'
        ];

        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue($postData);
        $this->dispatch($this->uri);

        /** @var SendRequestStageStub $pipelineStage */
        $pipelineStage = $this->_objectManager->get(SendRequestStage::class);

        // assert both, pending AND processed orders were sent to api
        self::assertCount(count($selectedOrderIds), $pipelineStage->apiRequests);

        // load shipments for all orders placed during test setup
        $fixtureOrderIds = array_map(function (Order $order) {
            return $order->getId();
        }, array_merge(self::$orders, self::$shippedOrders));

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipmentCollection->addFieldToFilter('order_id', ['in' => [$fixtureOrderIds]]);

        // assert every order now has one shipment
        $shipmentCount = count(self::$shippedOrders) + count($selectedPendingOrderIds);
        self::assertCount($shipmentCount, $shipmentCollection);

        /** @var ShipmentInterface $shipment */
        foreach ($shipmentCollection as $shipment) {
            /** @var ShipmentTrackInterface[] $tracks */
            $tracks = array_values($shipment->getTracks());
            if (in_array($shipment->getOrderId(), $selectedOrderIds)) {
                // requested orders should now have exactly one label and one track assigned
                self::assertStringStartsWith('%PDF-1', $shipment->getShippingLabel());
                self::assertCount(1, $tracks);
                self::assertStringStartsWith((string) $shipment->getOrderId(), $tracks[0]->getTrackNumber());
            } else {
                // not selected orders should remain untouched
                self::assertEmpty($shipment->getShippingLabel());
                self::assertEmpty($tracks);
            }
        }
    }
}
