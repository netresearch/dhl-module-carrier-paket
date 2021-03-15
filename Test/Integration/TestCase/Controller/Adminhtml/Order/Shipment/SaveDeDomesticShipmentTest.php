<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Model\Carrier\Paket;
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
class SaveDeDomesticShipmentTest extends SaveShipmentTest
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
     * @test
     * @dataProvider postDataProvider
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
}
