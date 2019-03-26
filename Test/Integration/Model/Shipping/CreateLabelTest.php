<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class CreateLabelTest
 *
 * @package Dhl\ShippingCore\Test\Integration
 */
class CreateLabelTest extends TestCase
{
    /**
     * @var ShipmentLoader
     */
    private $shipmentLoader;

    /**
     * @var LabelGenerator
     */
    private $labelGenerator;

    protected function setUp()
    {
        parent::setUp();

        $this->shipmentLoader = Bootstrap::getObjectManager()->get(ShipmentLoader::class);
        $this->labelGenerator = Bootstrap::getObjectManager()->get(LabelGenerator::class);
    }

    /**
     * Provide order and packaging popup request data.
     *
     * @todo(nr): create order fixtures to put into the process.
     * @todo(nr): this will get lengthy, move to separate provider class.
     * @todo(nr): use order and request data from fixture.
     *
     * @return mixed[]
     */
    public function domesticShipmentDataProvider()
    {
        $order = Bootstrap::getObjectManager()->create(OrderInterface::class, ['data' => []]);

        $shipmentDataDe = [
            'items' => [($orderItemId = 124) => ($qty = '1')],
            'create_shipping_label' => '1',
        ];
        $packagesDataDe = [
            1 => [
                'params' => [
                    'container' => 'V01PAK',
                    'weight' => '0.9',
                    'customs_value' => '55',
                    'length' => '',
                    'width' => '',
                    'height' => '',
                    'weight_units' => 'KILOGRAM',
                    'dimension_units' => 'CENTIMETER',
                    'content_type' => '',
                    'content_type_other' => '',
                ],
                'items' => [
                    124 => [
                        'qty' => '1',
                        'customs_value' => '55',
                        'price' => '55.0000',
                        'name' => 'Cruise Dual Analog Watch',
                        'weight' => '',
                        'product_id' => '22',
                        'order_item_id' => '124',
                    ],
                ],
            ],
        ];

        $requests = [
            'de-de' => [$order, $shipmentDataDe, $packagesDataDe],
        ];

        return $requests;
    }

    /**
     * Test the leanest use case: Domestic shipments with no extras.
     *
     * todo(nr): mock SDK access. test focus is the correct transformation of POST data into request builder args.
     *
     * @test
     * @dataProvider domesticShipmentDataProvider
     *
     * @param OrderInterface $order The order to request a shipping label for
     * @param string[] $shipmentData The shipment data coming from the packaging popup (POST['shipment']).
     * @param string[] $packagesData The packages data coming from the packaging popup (POST['packages']).
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function domesticShipment(OrderInterface $order, array $shipmentData, array $packagesData)
    {
        $this->markTestIncomplete('Pass in order fixture.');

        /** @var Http $request */
        $request = Bootstrap::getObjectManager()->create(Http::class);
        $request->setPostValue('shipment', $shipmentData);
        $request->setPostValue('packages', $packagesData);

        $this->shipmentLoader->setOrderId($order->getEntityId());
        $this->shipmentLoader->setShipment($shipmentData);
        $shipment = $this->shipmentLoader->load();
        $shipment->register();

        self::assertEmpty($shipment->getShippingLabel());
        $this->labelGenerator->create($shipment, $request);
        self::assertNotEmpty($shipment->getShippingLabel());
    }
}
