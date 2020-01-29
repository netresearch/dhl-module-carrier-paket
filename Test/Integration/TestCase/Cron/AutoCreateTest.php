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
use Dhl\Sdk\Paket\Bcs\Exception\DetailedServiceException;
use Dhl\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Dhl\ShippingCore\Cron\AutoCreate;
use Dhl\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressDe;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct2;
use Dhl\ShippingCore\Test\Integration\Fixture\OrderFixture;
use Dhl\ShippingCore\Test\Integration\Fixture\ShipmentFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Class AutoCreateTest
 *
 * @author Christoph Aßmann <christoph.assmann@netresearch.de>
 *
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

        $pendingOrder = OrderFixture::createOrder(new AddressDe(), [new SimpleProduct()], $shippingMethod);

        /** @var Shipment $pendingShipment */
        $pendingShipment = ShipmentFixture::createShipment(new AddressDe(), [new SimpleProduct2()], $shippingMethod);

        /** @var Shipment $processedShipment */
        $processedShipment = ShipmentFixture::createShipment(
            new AddressDe(),
            [new SimpleProduct(), new SimpleProduct2()],
            $shippingMethod,
            ['123456'],
            true
        );

        self::$orders = [
            $pendingOrder, // order with no shipment
            $pendingShipment->getOrder(), // order with shipment but no label
            $processedShipment->getOrder(), // order with shipment and label
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
        self::$orders = [OrderFixture::createProcessedOrder(new AddressDe(), [new SimpleProduct()], $shippingMethod)];
    }

    /**
     * Create order fixture for DE recipient address with multiple shipments, one of them with failed label.
     *
     * @throws \Exception
     */
    public static function createPartialShipments()
    {
        $shippingMethod = Paket::CARRIER_CODE . '_flatrate';

        // prepare all shipments
        /** @var Shipment $shipment */
        $shipments = ShipmentFixture::createPartialShipments(
            new AddressDe(),
            [new SimpleProduct(), new SimpleProduct2()],
            $shippingMethod
        );

        array_walk(
            $shipments,
            function (Shipment $shipment) {
                $shipment->setShippingLabel('%PDF-1.4');
                $shipment->save();
            }
        );

        // let one shipment fail
        /** @var Shipment $failedShipment */
        $failedShipment = array_pop($shipments);
        $failedShipment->setShippingLabel(null);
        $failedShipment->save();

        /** @var LabelStatusManagementInterface $labelStatusManagement */
        $labelStatusManagement = Bootstrap::getObjectManager()->get(LabelStatusManagementInterface::class);
        $labelStatusManagement->setLabelStatusFailed($failedShipment->getOrder());

        self::$orders = [$failedShipment->getOrder()];
    }

    /**
     * Clearing the order's shipment collection does not reset `_totalRecords` on M2.2, need to create fresh instance.
     *
     * @see \Magento\Framework\Data\Collection::clear
     *
     * @param Order $order
     * @return Collection
     */
    private function getShipmentsCollection(Order $order)
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
     * @test
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
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_enabled 0
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/retry_failed_shipments 0
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_order_status processing
     */
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
     * @test
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
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_enabled 1
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/retry_failed_shipments 0
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_order_status processing
     */
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
            ->setMethods(['createShipments'])
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
            ->setMethods(['create'])
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
     * @test
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
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_enabled 1
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/retry_failed_shipments 1
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_order_status processing
     */
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
     * @test
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
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_enabled 1
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/retry_failed_shipments 0
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_order_status processing
     */
    public function skipFailedShipment()
    {
        $order = self::$orders[0];

        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = Bootstrap::getObjectManager()->get(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$order->getEntityId()]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_FAILED, $labelStatus[$order->getEntityId()]);
        self::assertEquals('processing', $order->getStatus());

        // assert "createShipments" is not called
        $serviceMock = $this->getMockBuilder(ShipmentService::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['createShipments'])
                            ->getMock();
        $serviceMock->expects($this->never())->method('createShipments');

        $serviceFactoryMock = $this->getMockBuilder(ShipmentServiceFactory::class)
                                   ->disableOriginalConstructor()
                                   ->setMethods(['create'])
                                   ->getMock();
        $serviceFactoryMock->method('create')->willReturn($serviceMock);

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
     * @test
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
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_enabled 1
     * @magentoConfigFixture default_store dhlshippingsolutions/dhlglobalwebservices/bulk_settings/retry_failed_shipments 1
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/dhlshippingsolutions/dhlglobalwebservices/bulk_settings/cron_order_status processing
     */
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
                            ->setMethods(['createShipments'])
                            ->getMock();
        $serviceMock->expects($this->once())->method('createShipments')->willThrowException($serviceException);

        $serviceFactoryMock = $this->getMockBuilder(ShipmentServiceFactory::class)
                                   ->disableOriginalConstructor()
                                   ->setMethods(['create'])
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
