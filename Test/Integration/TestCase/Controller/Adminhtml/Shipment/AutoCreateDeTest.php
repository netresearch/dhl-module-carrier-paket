<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Shipment;

use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressDe;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct;
use Dhl\ShippingCore\Test\Integration\Fixture\OrderFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;

/**
 * Class AutoCreateDeTest
 * @package Dhl\Paket\Test\Integration\Controller
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class AutoCreateDeTest extends AutoCreateTest
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
     * @throws \Exception
     */
    public static function createOrders()
    {
        self::$orders = [
            OrderFixture::createOrder(new AddressDe(), [new SimpleProduct()], 'dhlpaket_flatrate'),
            OrderFixture::createOrder(new AddressDe(), [new SimpleProduct()], 'dhlpaket_flatrate'),
            OrderFixture::createOrder(new AddressDe(), [new SimpleProduct()], 'dhlpaket_flatrate'),
        ];

        self::$shippedOrders = [
            OrderFixture::createProcessedOrder(new AddressDe(), [new SimpleProduct()], 'dhlpaket_flatrate'),
            OrderFixture::createProcessedOrder(new AddressDe(), [new SimpleProduct()], 'dhlpaket_flatrate'),
            OrderFixture::createProcessedOrder(new AddressDe(), [new SimpleProduct()], 'dhlpaket_flatrate'),
        ];
    }

    /**
     * @test
     * @magentoDataFixture createOrders
     *
     * @magentoConfigFixture default/dhlshippingsolutions/dhlglobalwebservices/retry_failed_shipments 0
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
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
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

        // assert only pending orders were sent to api
        self::assertCount(count($selectedPendingOrderIds), $this->shipmentService->shipmentOrders);

        // load shipments for all orders placed during test setup
        $fixtureOrderIds = array_map(function (Order $order) {
            return $order->getId();
        }, array_merge(self::$orders, self::$shippedOrders));

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->objectManager->create(Collection::class);
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
                self::assertStringStartsWith($shipment->getOrderId(), $tracks[0]->getTrackNumber());
            } else {
                // existing orders should remain untouched
                self::assertEmpty($shipment->getShippingLabel());
                self::assertEmpty($tracks);
            }
        }
    }

    /**
     * @test
     * @magentoDataFixture createOrders
     *
     * @magentoConfigFixture default/dhlshippingsolutions/dhlglobalwebservices/retry_failed_shipments 1
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
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
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

        // assert both, pending AND processed orders were sent to api
        self::assertCount(count($selectedOrderIds), $this->shipmentService->shipmentOrders);

        // load shipments for all orders placed during test setup
        $fixtureOrderIds = array_map(function (Order $order) {
            return $order->getId();
        }, array_merge(self::$orders, self::$shippedOrders));

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->objectManager->create(Collection::class);
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
                self::assertStringStartsWith($shipment->getOrderId(), $tracks[0]->getTrackNumber());
            } else {
                // not selected orders should remain untouched
                self::assertEmpty($shipment->getShippingLabel());
                self::assertEmpty($tracks);
            }
        }
    }
}
