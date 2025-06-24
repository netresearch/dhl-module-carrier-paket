<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Cron;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Pipeline\CreateShipments\Stage\SendRequestStage;
use Dhl\Paket\Model\Webservice\ShipmentService;
use Dhl\Paket\Model\Webservice\ShipmentServiceFactory;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub;
use Dhl\Paket\Test\Integration\TestDouble\ShipmentServiceStub;
use Dhl\Sdk\ParcelDe\Shipping\Exception\DetailedServiceException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Netresearch\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Netresearch\ShippingCore\Cron\AutoCreate;
use Netresearch\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use Netresearch\ShippingCore\Test\Integration\Fixture\OrderBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Sales\InvoiceBuilder;
use TddWizard\Fixtures\Sales\OrderFixture;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;
use TddWizard\Fixtures\Sales\ShipmentBuilder;

/**
 * @magentoAppArea crontab
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class AutoCreateTest extends TestCase
{
    /**
     * @var OrderInterface[]|Order[]
     */
    private static $orders;

    /**
     * Create order fixtures for DE recipient address.
     *
     * @throws \Exception
     */
    public static function createOrders()
    {
        $shippingMethod = Paket::CARRIER_CODE . '_flatrate';

        $pendingOrder = OrderBuilder::anOrder()->withShippingMethod($shippingMethod)->build();

        $processingOrder = OrderBuilder::anOrder()->withShippingMethod($shippingMethod)->build();
        ShipmentBuilder::forOrder($processingOrder)->build();

        $completeOrder = OrderBuilder::anOrder()
            ->withShippingMethod($shippingMethod)
            ->withLabelStatus(LabelStatusManagementInterface::LABEL_STATUS_PROCESSED)
            ->build();
        InvoiceBuilder::forOrder($completeOrder)->build();
        ShipmentBuilder::forOrder($completeOrder)->withTrackingNumbers('123456')->build();

        self::$orders = [
            $pendingOrder, // order with no shipment
            $processingOrder, // order with shipment but no label
            $completeOrder, // order with shipment and label and invoice
        ];
    }

    /**
     * Create order fixture for DE recipient address with shipment and label status "Failed".
     *
     * @throws \Exception
     */
    public static function createFailedShipment()
    {
        $shippingMethod = Paket::CARRIER_CODE . '_flatrate';

        $order = OrderBuilder::anOrder()
            ->withShippingMethod($shippingMethod)
            ->withLabelStatus(LabelStatusManagementInterface::LABEL_STATUS_FAILED)
            ->build();
        ShipmentBuilder::forOrder($order)->build();

        self::$orders = [$order];
    }

    /**
     * Create order fixture for DE recipient address with multiple shipments, one of them with failed label.
     *
     * @throws \Exception
     */
    public static function createPartialShipments()
    {
        $shippingMethod = Paket::CARRIER_CODE . '_flatrate';

        $order = OrderBuilder::anOrder()
            ->withShippingMethod($shippingMethod)
            ->withLabelStatus(LabelStatusManagementInterface::LABEL_STATUS_FAILED)
            ->withProducts(
                ProductBuilder::aSimpleProduct()->withSku('foo'),
                ProductBuilder::aSimpleProduct()->withSku('bar')
            )->build();

        foreach ($order->getItems() as $orderItem) {
            $shipmentBuilder = ShipmentBuilder::forOrder($order)
                ->withItem((int) $orderItem->getItemId(), (int) $orderItem->getQtyOrdered());

            if ($orderItem->getSku() === 'foo') {
                $shipmentBuilder = $shipmentBuilder->withTrackingNumbers('123456');
            }

            $shipmentBuilder->build();
        }

        self::$orders = [$order];
    }

    /**
     * @throws \Exception
     */
    public static function createOrdersRollback()
    {
        self::rollback();
    }

    /**
     * @throws \Exception
     */
    public static function createFailedShipmentRollback()
    {
        self::rollback();
    }

    /**
     * @throws \Exception
     */
    public static function createPartialShipmentsRollback()
    {
        self::rollback();
    }

    /**
     * @throws \Exception
     */
    private static function rollback()
    {
        try {
            $orderFixtures = array_map(
                static function (OrderInterface $order) {
                    return new OrderFixture($order);
                },
                self::$orders
            );

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
     * Clearing the order's shipment collection does not reset `_totalRecords` on M2.2, need to create fresh instance.
     *
     * @see \Magento\Framework\Data\Collection::clear
     *
     * @param Order $order
     * @return Collection
     */
    private function getShipmentsCollection(Order $order): Collection
    {
        /** @var Collection $collection */
        $collection = Bootstrap::getObjectManager()->create(Collection::class);
        $collection->setOrderFilter($order);

        return $collection;
    }

    /**
     * Scenario: Multiple orders exist, the cron/autocreate feature is disabled via configuration.
     *
     * Assert that the process terminates early.
     *
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
     * @magentoConfigFixture default_store shipping/batch_processing/shipping_label/cron_enabled 0
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/cron_order_status processing
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/retry_failed_shipments 0
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function cronTerminatesWhenDisabledViaConfig()
    {
        $logger = new TestLogger();

        /** @var AutoCreate $autoCreate */
        $autoCreate = Bootstrap::getObjectManager()->create(AutoCreate::class, ['logger' => $logger]);
        $autoCreate->execute();

        self::assertTrue($logger->hasDebugThatContains('automatic label retrieval is not enabled'));
    }

    /**
     * Scenario: Multiple orders exist with different order status, web service request fails.
     *
     * - Assert that only orders with the configured status are sent to the web service, others remain untouched.
     * - Assert that order comments were added on web service failure.
     * - Assert that label status "Failed" was set on web service failure.
     *
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
     * @magentoConfigFixture default_store shipping/batch_processing/shipping_label/cron_enabled 1
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/cron_order_status processing
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/retry_failed_shipments 0
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function orderWithDeselectedStatusIsNotProcessed()
    {
        $pendingOrder = null;
        $processingOrder = null;
        $completeOrder = null;

        foreach (self::$orders as $order) {
            if ($order->getStatus() === 'pending') {
                $pendingOrder = $order;
            } elseif ($order->getStatus() === 'processing') {
                $processingOrder = $order;
            } elseif ($order->getStatus() === 'complete') {
                $completeOrder = $order;
            }
        }

        // assert one order for each status was created
        self::assertInstanceOf(OrderInterface::class, $pendingOrder);
        self::assertInstanceOf(OrderInterface::class, $processingOrder);
        self::assertInstanceOf(OrderInterface::class, $completeOrder);

        $serviceError = 'failures must fail.';
        $serviceException = new DetailedServiceException($serviceError);
        $serviceMock = $this->getMockBuilder(ShipmentService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serviceMock
            ->expects($this->once())
            ->method('createShipments')
            ->with($this->callback(function (array $shipmentOrders) {
                return (count($shipmentOrders) === 1);
            }))
            ->willThrowException($serviceException);

        $serviceFactoryMock = $this->getMockBuilder(ShipmentServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serviceFactoryMock->method('create')->willReturn($serviceMock);

        Bootstrap::getObjectManager()->addSharedInstance($serviceFactoryMock, ShipmentServiceFactory::class);

        $logger = new TestLogger();

        /** @var AutoCreate $autoCreate */
        $autoCreate = Bootstrap::getObjectManager()->create(AutoCreate::class, ['logger' => $logger]);
        $autoCreate->execute();

        // assert no shipments were created for the pending order
        $shipments = $this->getShipmentsCollection($pendingOrder)->getItems();
        self::assertEmpty($shipments);

        // assert failure comment was added for the processing order
        $shipments = $this->getShipmentsCollection($processingOrder)->getItems();
        self::assertNotEmpty($shipments);

        /** @var Shipment $shipment */
        $shipment = array_pop($shipments);
        $failureCommentCount = $shipment->getCommentsCollection(true)
            ->addFieldToFilter(OrderStatusHistoryInterface::COMMENT, ['like' => "%$serviceError%"])
            ->getSize();
        self::assertSame(1, $failureCommentCount);

        // assert service error is logged
        self::assertTrue($logger->hasErrorThatContains($serviceError));

        // assert order's label status was updated according to web service response
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = Bootstrap::getObjectManager()->get(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$shipment->getOrderId()]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_FAILED, $labelStatus[$shipment->getOrderId()]);
    }

    /**
     * Scenario: An orders exists with multiple shipments, one has no label yet, web service request succeeds.
     *
     * - Assert that the missing label is requested from the web service.
     * - Assert that label pdf was added on web service success.
     * - Assert that label status was switched to "processed" on web service success.
     *
     * @magentoDataFixture createPartialShipments
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
     * @magentoConfigFixture default_store shipping/batch_processing/shipping_label/cron_enabled 1
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/cron_order_status processing
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/retry_failed_shipments 1
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partialShipmentIsProcessed()
    {
        $order = self::$orders[0];

        // verify initial status
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = Bootstrap::getObjectManager()->get(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$order->getEntityId()]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_FAILED, $labelStatus[$order->getEntityId()]);
        self::assertEquals('processing', $order->getStatus());

        $shipmentsWithNoLabels = $this->getShipmentsCollection($order)
            ->addFieldToFilter(Shipment::SHIPPING_LABEL, ['null' => true])
            ->getSize();
        self::assertSame(1, $shipmentsWithNoLabels);

        // prepare positive response
        Bootstrap::getObjectManager()->configure(['preferences' => [
            SendRequestStage::class => SendRequestStageStub::class,
            ShipmentService::class => ShipmentServiceStub::class
        ]]);

        /** @var AutoCreate $autoCreate */
        $autoCreate = Bootstrap::getObjectManager()->create(AutoCreate::class);
        $autoCreate->execute();

        // assert that the pending shipment was sent to the web service
        /** @var SendRequestStageStub $pipelineStage */
        $pipelineStage = Bootstrap::getObjectManager()->get(SendRequestStage::class);

        // assert that one shipment order was sent to the web service (order details are not observable)
        self::assertCount(1, $pipelineStage->apiRequests);

        // assert all shipments have a label now
        $shipmentsWithNoLabels = $this->getShipmentsCollection($order)
                          ->addFieldToFilter(Shipment::SHIPPING_LABEL, ['null' => true])
                          ->getSize();
        self::assertSame(0, $shipmentsWithNoLabels);

        // assert label status was updated
        $labelStatus = $labelStatusProvider->getLabelStatus([$order->getEntityId()]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_PROCESSED, $labelStatus[$order->getEntityId()]);
        self::assertEquals('processing', $order->getStatus());
    }

    /**
     * Scenario: An order exist with with label status "Failed", config is "Retry Failed: No".
     *
     * Assert that the order is not sent to the web service again.
     *
     * @magentoDataFixture createFailedShipment
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
     * @magentoConfigFixture default_store shipping/batch_processing/shipping_label/cron_enabled 1
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/cron_order_status processing
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/retry_failed_shipments 0
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function skipFailedShipment()
    {
        $order = self::$orders[0];

        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = Bootstrap::getObjectManager()->get(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$order->getEntityId()]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_FAILED, $labelStatus[$order->getEntityId()]);
        self::assertEquals('processing', $order->getStatus());

        // assert "createShipments" is not called
        $serviceMock = $this->createMock(ShipmentService::class)->expects($this->never())->method('createShipments');
        $serviceFactoryMock = $this->createConfiguredMock(ShipmentServiceFactory::class, ['create' => $serviceMock]);

        Bootstrap::getObjectManager()->addSharedInstance($serviceFactoryMock, ShipmentServiceFactory::class);

        $logger = new TestLogger();

        /** @var AutoCreate $autoCreate */
        $autoCreate = Bootstrap::getObjectManager()->create(AutoCreate::class, ['logger' => $logger]);
        $autoCreate->execute();

        $message = sprintf('Shipment(s) for the order(s) %s could not be created.', $order->getIncrementId());
        self::assertTrue($logger->hasErrorThatContains($message));
    }

    /**
     * Scenario: An order exist with with label status "Failed", config is "Retry Failed: Yes".
     *
     * Assert that the order is sent to the web service again.
     *
     * @magentoDataFixture createFailedShipment
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
     * @magentoConfigFixture default_store shipping/batch_processing/shipping_label/cron_enabled 1
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/cron_order_status processing
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/retry_failed_shipments 1
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function retryFailedShipment()
    {
        $order = self::$orders[0];

        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = Bootstrap::getObjectManager()->get(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$order->getEntityId()]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_FAILED, $labelStatus[$order->getEntityId()]);
        self::assertEquals('processing', $order->getStatus());

        // assert "createShipments" is called, returns service error
        $serviceError = 'failures must fail.';
        $serviceException = new DetailedServiceException($serviceError);
        $serviceMock = $this->getMockBuilder(ShipmentService::class)
                            ->disableOriginalConstructor()
                            ->getMock();
        $serviceMock->expects($this->once())->method('createShipments')->willThrowException($serviceException);

        $serviceFactoryMock = $this->getMockBuilder(ShipmentServiceFactory::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $serviceFactoryMock->method('create')->willReturn($serviceMock);

        Bootstrap::getObjectManager()->addSharedInstance($serviceFactoryMock, ShipmentServiceFactory::class);

        $logger = new TestLogger();

        /** @var AutoCreate $autoCreate */
        $autoCreate = Bootstrap::getObjectManager()->create(AutoCreate::class, ['logger' => $logger]);
        $autoCreate->execute();

        // assert service error is logged
        self::assertTrue($logger->hasErrorThatContains($serviceError));
    }
}
