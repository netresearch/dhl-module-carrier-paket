<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Shipment;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Pipeline\DeleteShipments\Stage\SendRequestStage;
use Dhl\Paket\Model\Webservice\ShipmentService;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub;
use Dhl\Paket\Test\Integration\TestDouble\ShipmentServiceStub;
use Dhl\Sdk\ParcelDe\Shipping\Exception\ServiceException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\TrackInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Netresearch\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Netresearch\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use Netresearch\ShippingCore\Test\Integration\Fixture\OrderBuilder;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Sales\ShipmentBuilder;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class CancelTest extends AbstractBackendController
{
    /**
     * The resource used to authorize action
     *
     * @var string
     */
    protected $resource = 'Magento_Sales::ship';

    /**
     * @var string
     */
    protected $httpMethod = 'GET';

    /**
     * The uri at which to access the controller
     *
     * @var string
     */
    protected $uri = 'backend/nrshipping/shipment/cancel';

    /**
     * @var OrderInterface[]
     */
    private static $orders = [];

    /**
     * @var ShipmentInterface[]|Shipment[]
     */
    private static $shipments = [];

    protected function setUp(): void
    {
        parent::setUp();

        // configure web service response
        $this->_objectManager->configure(
            [
                'preferences' => [
                    SendRequestStage::class => SendRequestStageStub::class,
                    ShipmentService::class => ShipmentServiceStub::class,
                ]
            ]
        );
    }

    public function shipmentProvider()
    {
        return [
            'single_package' => [
                function () {
                    return self::$shipments['single_package'];
                },
            ],
            'multi_package' => [
                function () {
                    return self::$shipments['multi_package'];
                },
            ],
        ];
    }

    /**
     * Create shipment fixtures with tracks for DE recipient address.
     *
     * @throws \Exception
     */
    public static function createShipments()
    {
        $shippingMethod = sprintf('%s_flatrate', Paket::CARRIER_CODE);
        $packages = [
            'single_package' => ['123456'],
            'multi_package' => ['123456', '654321'],
        ];

        foreach ($packages as $type => $trackingNumbers) {
            $order = OrderBuilder::anOrder()
                ->withShippingMethod($shippingMethod)
                ->withLabelStatus(LabelStatusManagementInterface::LABEL_STATUS_PROCESSED)
                ->withProducts(
                    ProductBuilder::aSimpleProduct()->withSku('foo'),
                    ProductBuilder::aSimpleProduct()->withSku('bar')
                )->build();

            self::$shipments[$type] = ShipmentBuilder::forOrder($order)
                ->withTrackingNumbers(...$trackingNumbers)
                ->build();
            self::$orders[] = $order;
        }
    }

    /**
     * Scenario: A shipment with tracks gets cancelled. Web service request succeeds.
     *
     * - Assert that shipping label was deleted
     * - Assert that tracks were removed
     * - Assert that label status is set back to pending
     *
     * @test
     * @dataProvider shipmentProvider
     * @magentoDataFixture createShipments
     *
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 91
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 04229
     * @magentoConfigFixture default_store general/store_information/city Leipzig
     * @magentoConfigFixture default_store general/store_information/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 91
     * @magentoConfigFixture default_store shipping/origin/postcode 04229
     * @magentoConfigFixture default_store shipping/origin/city Leipzig
     * @magentoConfigFixture default_store shipping/origin/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @param callable $getShipment
     */
    public function trackDeletionSucceeds(callable $getShipment)
    {
        /** @var ShipmentInterface $fixtureShipment */
        $fixtureShipment = $getShipment();

        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        /** @var ShipmentRepositoryInterface $shipmentRepository */
        $shipmentRepository = $this->_objectManager->get(ShipmentRepositoryInterface::class);

        // assert shipment state before cancellation
        self::assertNotNull($fixtureShipment->getShippingLabel());
        self::assertNotEmpty($fixtureShipment->getTracks());
        $labelStatus = $labelStatusProvider->getLabelStatus([$fixtureShipment->getOrderId()]);
        self::assertSame(
            LabelStatusManagementInterface::LABEL_STATUS_PROCESSED,
            $labelStatus[$fixtureShipment->getOrderId()]
        );

        // dispatch (cancel)
        $uri = sprintf('%s/shipment_id/%s', $this->uri, $fixtureShipment->getEntityId());
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($uri);
        $shipment = $shipmentRepository->get($fixtureShipment->getEntityId());

        // assert shipment state after cancellation
        $labelStatus = $labelStatusProvider->getLabelStatus([$shipment->getOrderId()]);

        self::assertNull($shipment->getShippingLabel());
        self::assertEmpty($shipment->getTracks());
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_PENDING, $labelStatus[$shipment->getOrderId()]);
    }

    /**
     * Scenario: A shipment with tracks gets cancelled. Web service request fails.
     *
     * - Assert that shipping label remains the same
     * - Assert that no tracks were removed
     * - Assert that label status remains the same
     *
     * @test
     * @dataProvider shipmentProvider
     * @magentoDataFixture createShipments
     *
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 91
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 04229
     * @magentoConfigFixture default_store general/store_information/city Leipzig
     * @magentoConfigFixture default_store general/store_information/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 91
     * @magentoConfigFixture default_store shipping/origin/postcode 04229
     * @magentoConfigFixture default_store shipping/origin/city Leipzig
     * @magentoConfigFixture default_store shipping/origin/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @param callable $getShipment
     */
    public function trackDeletionFails(callable $getShipment)
    {
        /** @var ShipmentInterface $fixtureShipment */
        $fixtureShipment = $getShipment();

        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        /** @var ShipmentRepositoryInterface $shipmentRepository */
        $shipmentRepository = $this->_objectManager->get(ShipmentRepositoryInterface::class);

        // assert shipment state before cancellation
        $labelStatus = $labelStatusProvider->getLabelStatus([$fixtureShipment->getOrderId()]);
        $fixtureLabelStatus = $labelStatus[$fixtureShipment->getOrderId()];
        $fixtureTrackNumbers = array_map(
            function (TrackInterface $track) {
                return $track->getTrackNumber();
            },
            array_values($fixtureShipment->getTracks())
        );

        self::assertNotNull($fixtureShipment->getShippingLabel());
        self::assertNotEmpty($fixtureTrackNumbers);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_PROCESSED, $fixtureLabelStatus);

        // dispatch (cancel)
        /** @var SendRequestStageStub $stage */
        $stage = $this->_objectManager->get(SendRequestStage::class);
        $stage->responseCallback = function () {
            return new ServiceException('too late.');
        };

        $uri = sprintf('%s/shipment_id/%s', $this->uri, $fixtureShipment->getEntityId());
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($uri);
        $shipment = $shipmentRepository->get($fixtureShipment->getEntityId());

        // assert shipment state after cancellation
        $labelStatus = $labelStatusProvider->getLabelStatus([$shipment->getOrderId()]);
        $trackNumbers = array_map(
            function (TrackInterface $track) {
                return $track->getTrackNumber();
            },
            array_values($shipment->getTracks())
        );

        self::assertSame($fixtureShipment->getShippingLabel(), $shipment->getShippingLabel());
        self::assertSame($fixtureTrackNumbers, $trackNumbers);
        self::assertSame($fixtureLabelStatus, $labelStatus[$shipment->getOrderId()]);
    }

    /**
     * Scenario: A shipment with multiple tracks gets cancelled. Some of the track deletion requests gave errors.
     *
     * - Assert that the shipping label is removed
     * - Assert that all tracks are removed
     * - Assert that label status is set back to pending
     *
     * @test
     * @magentoDataFixture createShipments
     *
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 91
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 04229
     * @magentoConfigFixture default_store general/store_information/city Leipzig
     * @magentoConfigFixture default_store general/store_information/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 91
     * @magentoConfigFixture default_store shipping/origin/postcode 04229
     * @magentoConfigFixture default_store shipping/origin/city Leipzig
     * @magentoConfigFixture default_store shipping/origin/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function trackDeletionSucceedsPartially()
    {
        $fixtureShipment = self::$shipments['multi_package'];

        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        /** @var ShipmentRepositoryInterface $shipmentRepository */
        $shipmentRepository = $this->_objectManager->get(ShipmentRepositoryInterface::class);

        // assert shipment state before cancellation
        $labelStatus = $labelStatusProvider->getLabelStatus([$fixtureShipment->getOrderId()]);
        $fixtureLabelStatus = $labelStatus[$fixtureShipment->getOrderId()];
        $fixtureTrackNumbers = array_map(
            function (TrackInterface $track) {
                return $track->getTrackNumber();
            },
            array_values($fixtureShipment->getTracks())
        );

        self::assertNotNull($fixtureShipment->getShippingLabel());
        self::assertGreaterThan(1, count($fixtureTrackNumbers));
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_PROCESSED, $fixtureLabelStatus);

        // dispatch (cancel)
        $chunks = array_chunk($fixtureTrackNumbers, intval(count($fixtureTrackNumbers) / 2));
        $success = $chunks[0];

        /** @var SendRequestStageStub $stage */
        $stage = $this->_objectManager->get(SendRequestStage::class);
        $stage->responseCallback = function () use ($success) {
            return $success;
        };

        $uri = sprintf('%s/shipment_id/%s', $this->uri, self::$shipments['multi_package']->getEntityId());
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($uri);
        $shipment = $shipmentRepository->get($fixtureShipment->getEntityId());

        // assert shipment state after cancellation
        $labelStatus = $labelStatusProvider->getLabelStatus([$shipment->getOrderId()]);

        self::assertNull($shipment->getShippingLabel());
        self::assertEmpty($shipment->getTracks());
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_PENDING, $labelStatus[$shipment->getOrderId()]);
    }
}
