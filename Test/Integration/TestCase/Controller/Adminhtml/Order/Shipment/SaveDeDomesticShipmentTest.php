<?php
/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Test\Integration\Generator\ShipmentRequestData;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressDe;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct;
use Dhl\ShippingCore\Test\Integration\Fixture\OrderFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;

/**
 * Test basic shipment creation for DE-DE route with no value-added services.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 *
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class SaveDeDomesticShipmentTest extends SaveShipmentTest
{
    /**
     * @var OrderInterface[]|Order[]
     */
    private static $orders;

    /**
     * Create order fixture for DE recipient address.
     *
     * @throws \Exception
     */
    public static function createOrder()
    {
        $shippingMethod = Paket::CARRIER_CODE . '_flatrate';
        $order = OrderFixture::createOrder(new AddressDe(), [new SimpleProduct()], $shippingMethod);
        self::$orders = [$order];
    }

    /**
     * @param OrderInterface|Order $order
     * @return mixed[]
     */
    private function getPackagingPostData(OrderInterface $order)
    {
        return ShipmentRequestData::generatePostData($order);
    }

    /**
     * Assert that label requests are properly processed to the carrier api and back.
     *
     * Possible entrypoints for test:
     *
     * @see \Dhl\Paket\Model\Carrier\Paket::requestToShipment
     * @see \Magento\Shipping\Model\Shipping\LabelGenerator::create
     * @see \Magento\Shipping\Controller\Adminhtml\Order\Shipment\CreateLabel::execute
     *
     * @test
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
     */
    public function saveShipment()
    {
        $order = self::$orders[0];

        // create packaging post data from order fixture
        $postData = $this->getPackagingPostData($order);

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue($postData);
        $this->getRequest()->setParam('order_id', $order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter($order)->getItems();
        $shipments = array_values($shipments);

        // assert that exactly one shipment was created for the order
        self::assertCount(1, $shipments);
        $shipment = $shipments[0];

        // assert shipping label was persisted with shipment
        self::assertStringStartsWith('%PDF-1', $shipment->getShippingLabel());

        // assert that one track was created per package
        $tracks = $shipment->getTracks();
        self::assertCount(count($postData['packages']), $tracks);

        //todo(nr): verify data passed to the api (shipment orders), e.g. addresses, shipment details, …
    }
}
