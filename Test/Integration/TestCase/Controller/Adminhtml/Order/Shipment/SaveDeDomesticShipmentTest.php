<?php
/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Test\Integration\Generator\ShipmentRequestData;
use Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment\SaveShipmentTest;
use Dhl\Sdk\Paket\Bcs\Service\ShipmentService\Shipment;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressDe;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct;
use Dhl\ShippingCore\Test\Integration\Fixture\OrderFixture;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test basic shipment creation for DE-DE route with no value-added services.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 *
 * @package Dhl\Paket\Test\Integration
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
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
        self::$order = OrderFixture::createOrder(new AddressDe(), new SimpleProduct(), Paket::CARRIER_CODE.'_flatrate');
    }

    /**
     * @param OrderInterface $order
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/dhl_paket_checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function saveShipment()
    {
        // create packaging post data from order fixture
        $postData = $this->getPackagingPostData(self::$order);

        // create shipments from packaging data and set as api response
        $createdShipments = array_map(
            function (string $sequenceNumber) {
                return new Shipment($sequenceNumber, "shipment $sequenceNumber", '', "pdf $sequenceNumber", '', '', '');
            },
            array_keys($postData['packages'])
        );
        $this->shipmentService->setCreatedShipments($createdShipments);

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue($postData);
        $this->getRequest()->setParam('order_id', self::$order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = Bootstrap::getObjectManager()->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter(self::$order)->getItems();

        // assert that shipments count equals packages count
        self::assertCount(count($postData['packages']), $shipments);

        // assert that labels and tracks were persisted with the shipment
        $createdTracks = array_map(
            function (Shipment $shipment) {
                return $shipment->getShipmentNumber();
            },
            $createdShipments
        );

        array_walk(
            $shipments,
            function (ShipmentInterface $shipment) use ($createdTracks) {
                self::assertNotEmpty($shipment->getShippingLabel());

                foreach ($shipment->getTracks() as $track) {
                    self::assertContains($track->getTrackNumber(), $createdTracks);
                }
            }
        );
        //todo(nr): verify data passed to the api (shipment orders), e.g. addresses, shipment details, …
    }
}
