<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Pipeline\CreateShipments\Stage\SendRequestStage;
use Dhl\Paket\Test\Integration\Provider\Controller\SaveShipment\PostDataProvider;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Netresearch\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Netresearch\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use TddWizard\Fixtures\Sales\OrderBuilder;
use TddWizard\Fixtures\Sales\OrderFixture;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;

/**
 * Test basic shipment creation for DE-DE route with no value-added services.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class SaveDeDomesticShipmentTest extends AbstractSaveShipmentController
{
    /**
     * Create order fixture for DE recipient address.
     *
     * @throws \Exception
     */
    public static function createOrder()
    {
        $shippingMethod = Paket::CARRIER_CODE . '_flatrate';
        self::$order = OrderBuilder::anOrder()->withShippingMethod($shippingMethod)->build();
    }

    /**
     * Roll back fixture.
     */
    public static function createOrderRollback()
    {
        try {
            OrderFixtureRollback::create()->execute(new OrderFixture(self::$order));
        } catch (\Exception $exception) {
            $argv = $_SERVER['argv'] ?? [];
            if (in_array('--verbose', $argv, true)) {
                $message = sprintf("Error during rollback: %s%s", $exception->getMessage(), PHP_EOL);
                register_shutdown_function('fwrite', STDERR, $message);
            }
        }
    }

    /**
     * Scenario: Two products are contained in an order, both are valid.
     *
     * - Assert that one shipment is created
     * - Assert that one tracking number is created per package
     * - Assert that label status is set to "Processed"
     *
     * @magentoDataFixture createOrder
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
     * @param callable $getPostData
     * @throws LocalizedException
     */
    #[\Override]
    #[\PHPUnit\Framework\Attributes\DataProvider('postDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function saveShipment(callable $getPostData)
    {
        // create packaging post data from order fixture
        $data = $getPostData();

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue('data', \json_encode($data));
        $this->getRequest()->setParam('order_id', self::$order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter(self::$order)->getItems();
        $shipments = array_values($shipments);

        // assert that exactly one shipment was created for the order
        self::assertCount(1, $shipments);
        $shipment = $shipments[0];

        // assert shipping label was persisted with shipment
        self::assertStringStartsWith('%PDF-1', $shipment->getShippingLabel());

        // assert that one track was created per package
        $tracks = $shipment->getTracks();
        self::assertCount(count($data['packages']), $tracks);

        // assert that the order's label status is "Processed"
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([self::$order->getEntityId()]);
        self::assertSame(
            LabelStatusManagementInterface::LABEL_STATUS_PROCESSED,
            $labelStatus[self::$order->getEntityId()]
        );
    }

    /**
     * Scenario: Two products are contained in an order with GoGreen Plus service enabled.
     *
     * - Assert that one shipment is created
     * - Assert that one tracking number is created per package
     * - Assert that label status is set to "Processed"
     * - Assert that GoGreen Plus service is processed correctly
     *
     * @magentoDataFixture createOrder
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge 2.50
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @throws LocalizedException
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function saveShipmentWithGoGreenPlus()
    {
        // test single package with GoGreen Plus
        $data = PostDataProvider::singlePackageDomesticWithGoGreenPlus(self::$order);

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue('data', \json_encode($data));
        $this->getRequest()->setParam('order_id', self::$order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter(self::$order)->getItems();
        $shipments = array_values($shipments);

        // assert that exactly one shipment was created for the order
        self::assertCount(1, $shipments);
        $shipment = $shipments[0];

        // assert shipping label was persisted with shipment
        self::assertStringStartsWith('%PDF-1', $shipment->getShippingLabel());

        // assert that one track was created per package
        $tracks = $shipment->getTracks();
        self::assertCount(count($data['packages']), $tracks);

        // assert that the order's label status is "Processed"
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([self::$order->getEntityId()]);
        self::assertSame(
            LabelStatusManagementInterface::LABEL_STATUS_PROCESSED,
            $labelStatus[self::$order->getEntityId()]
        );
    }

    /**
     * Scenario: Multiple packages in an order with GoGreen Plus service enabled.
     *
     * - Assert that one shipment is created
     * - Assert that tracking numbers are created for all packages
     * - Assert that label status is set to "Processed"
     * - Assert that GoGreen Plus service is processed correctly for all packages
     *
     * @magentoDataFixture createOrder
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge 2.50
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @throws LocalizedException
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function saveMultiPackageShipmentWithGoGreenPlus()
    {
        // test multi package with GoGreen Plus
        $data = PostDataProvider::multiPackageDomesticWithGoGreenPlus(self::$order);

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue('data', \json_encode($data));
        $this->getRequest()->setParam('order_id', self::$order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter(self::$order)->getItems();
        $shipments = array_values($shipments);

        // assert that exactly one shipment was created for the order
        self::assertCount(1, $shipments);
        $shipment = $shipments[0];

        // assert shipping label was persisted with shipment
        self::assertStringStartsWith('%PDF-1', $shipment->getShippingLabel());

        // assert that one track was created per package
        $tracks = $shipment->getTracks();
        self::assertCount(count($data['packages']), $tracks);

        // assert that the order's label status is "Processed"
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([self::$order->getEntityId()]);
        self::assertSame(
            LabelStatusManagementInterface::LABEL_STATUS_PROCESSED,
            $labelStatus[self::$order->getEntityId()]
        );
    }

    /**
     * Scenario: An order is shipped with enclosed return label and GoGreen Plus service enabled.
     *
     * - Assert that a shipment with label is created
     * - Assert that GoGreen Plus for the return label is transmitted within the dhlRetoure object
     *   as required by API spec 2.1.13
     * - Assert that the unknown top-level property returnShipmentGoGreenPlus is not transmitted
     *
     * @magentoDataFixture createOrder
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge 2.50
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment_receiver/company Returns-Center
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment_receiver/country_id DE
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment_receiver/postcode 04229
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment_receiver/city Leipzig
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment_receiver/street_name Nonnenstraße
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/shipment_defaults/services/return_shipment_receiver/street_number 11d
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @throws LocalizedException
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function saveShipmentWithEnclosedReturnAndGoGreenPlus()
    {
        $data = PostDataProvider::singlePackageDomesticWithEnclosedReturnAndGoGreenPlus(self::$order);

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue('data', \json_encode($data));
        $this->getRequest()->setParam('order_id', self::$order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter(self::$order)->getItems();

        // assert that a shipment with label was created for the order
        self::assertCount(1, $shipments);
        self::assertStringStartsWith('%PDF-1', array_values($shipments)[0]->getShippingLabel());

        /** @var SendRequestStageStub $pipelineStage */
        $pipelineStage = $this->_objectManager->get(SendRequestStage::class);
        self::assertNotEmpty($pipelineStage->apiRequests, 'No API request was built.');

        $shipmentOrder = \json_decode(\json_encode($pipelineStage->apiRequests[0]), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('returnShipmentGoGreenPlus', $shipmentOrder['services']);
        self::assertTrue($shipmentOrder['services']['dhlRetoure']['goGreenPlus'] ?? false);
    }
}
