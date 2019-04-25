<?php
/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Test\Integration\Fixture\OrderFixture;
use Dhl\ShippingCore\Model\Attribute\Backend\TariffNumber;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * LabelTest
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 *
 * @package Dhl\Paket\Test\Integration
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class CreateLabelTest extends AbstractBackendController
{
    /**
     * @var \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order
     */
    private static $order;

    /**
     * The resource used to authorize action
     *
     * @var string
     */
    protected $resource = 'Magento_Sales::shipment';

    /**
     * The uri at which to access the controller
     *
     * @var string
     */
    protected $uri = 'backend/admin/order_shipment/createLabel';

    /**
     * @var string
     */
    protected $httpMethod = 'POST';

    /**
     * Create order fixture for DE recipient address.
     *
     * @throws \Exception
     */
    public static function createPaketOrderToGermanyWithSimpleProduct()
    {
        self::$order = OrderFixture::createPaketOrderWithSimpleProduct(
            'Charles-de-Gaulle-Straße 20',
            'Bonn',
            '53113',
            'DE',
            88,
            [TariffNumber::CODE => '12345678'] //fixme(nr): hs code should not be required
        );
    }

    /**
     * Assert that label requests are properly processed to the carrier api and back.
     *
     * Possible entrypoints for test:
     * @see \Dhl\Paket\Model\Carrier\Paket::requestToShipment
     * @see \Magento\Shipping\Model\Shipping\LabelGenerator::create
     * @see \Magento\Shipping\Controller\Adminhtml\Order\Shipment\CreateLabel::execute
     *
     * @test
     * @magentoDataFixture createPaketOrderToGermanyWithSimpleProduct
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
     * @magentoConfigFixture current_store carriers/dhlpaket/dhl_paket_checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function domesticShipment()
    {
        $itemData = ['shipmentItems' => [], 'packageItems' => []];
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach (self::$order->getItems() as $item) {
            $itemData['shipmentItems'][$item->getItemId()] = $item->getQtyToShip();
            $itemData['packageItems'][$item->getItemId()] = [
                'qty' => $item->getQtyToShip(),
                'customs_value' => $item->getBasePrice(),
                'price' => $item->getBasePrice(),
                'name' => $item->getName(),
                'weight' => $item->getWeight(),
                'product_id' => $item->getProductId(),
                'order_item_id' => $item->getItemId(),
            ];
        }

        $postData = [
            'order_id' => self::$order->getEntityId(),
            'shipment' => [
                'items' => $itemData['shipmentItems'],
                'create_shipping_label' => '1',
            ],
            'packages' => [
                1 => [
                    'params' => [
                        'container' => 'V01PAK',
                        'weight' => 0.9,
                        'customs_value' => 55,
                        'length' => 30.0,
                        'width' => 20.0,
                        'height' => 20.0,
                        'weight_units' => 'KILOGRAM',
                        'dimension_units' => 'CENTIMETER',
                        'content_type' => '',
                        'content_type_other' => '',
                    ],
                    'items' => $itemData['packageItems'],
                ],

            ]
        ];

        $this->getRequest()->setPostValue($postData);

        //todo(nr): inject NullLogger or TestLogger
        //todo(nr): mock \Dhl\Paket\Webservice\ShipmentService::$shipmentService

        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = Bootstrap::getObjectManager()->get(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter(self::$order)->getItems();

        // assert that shipments count equals packages count
        self::assertCount(count($postData['packages']), $shipments);

        // assert that every shipment has a label
        array_walk($shipments, function (ShipmentInterface $shipment) {
            self::assertNotEmpty($shipment->getShippingLabel());
        });

        //todo(nr): check if tracking number exists for shipment(s)
    }

    /**
     * @test
     * @magentoDataFixture createPaketOrderToGermanyWithSimpleProduct
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
     * @magentoConfigFixture current_store carriers/dhlpaket/dhl_paket_checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function testAclHasAccess()
    {
        $this->getRequest()->setPostValue([
            'order_id' => self::$order->getEntityId(),
            'shipment' => []
        ]);

        parent::testAclHasAccess();
    }
}
