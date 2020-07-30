<?php

/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Pipeline\CreateShipments\Stage\SendRequestStage as CreationStage;
use Dhl\Paket\Test\Integration\Provider\Controller\SaveShipment\PostDataProvider;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub;
use Dhl\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Dhl\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Sales\OrderBuilder;
use TddWizard\Fixtures\Sales\OrderFixture;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;

/**
 * Assert that orders with cash on delivery payment can only be shipped together.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class NoPartialValidatorTest extends SaveShipmentTest
{

    /**
     * Create an order fixture for DE recipient address with an valid items.
     *
     * @throws \Exception
     */
    public static function createOrder()
    {
        $shippingMethod = Paket::CARRIER_CODE . '_flatrate';
        self::$order = OrderBuilder::anOrder()->withShippingMethod($shippingMethod)->withProducts(
            ProductBuilder::aSimpleProduct()->withWeight(1.1),
            ProductBuilder::aSimpleProduct()->withWeight(2.2)
        )->withPaymentMethod('cashondelivery')->build();
    }

    /**
     * @throws \Exception
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

    public function postDataProvider()
    {
        return [
            'multi_package' => [
                static function () {
                    return PostDataProvider::multiPackageDomesticWithCod(self::$order);
                },
            ],
        ];
    }

    /**
     * Scenario: Two products are contained in an order, both are valid and order is paid with cash on delivery.
     *
     * - Assert that no api requests were made
     * - Assert that controller response contains validation error message
     * - Assert that no shipments are created
     * - Assert that label status is set to "Failed"
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlglobalwebservices/cod_methods cashondelivery
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
        $orderId = self::$order->getEntityId();
        $packages = $getPostData();

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue('data', \json_encode($packages));
        $this->getRequest()->setParam('order_id', $orderId);
        $this->dispatch($this->uri);

        /** @var SendRequestStageStub $pipelineStage */
        $pipelineStage = $this->_objectManager->get(CreationStage::class);

        // assert no orders were sent to api
        self::assertCount(0, $pipelineStage->apiRequests);

        $responseBody = json_decode($this->getResponse()->getBody());

        // assert packaging popup receives validator error message for display
        self::assertTrue($responseBody->error);
        self::assertStringEndsWith(
            'Please ship the entire order in one package or deselect the service.',
            $responseBody->message
        );

        // assert that no shipments were created for the order
        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        self::assertSame(0, $shipmentCollection->setOrderFilter($orderId)->getSize());

        // assert that the order's label status is "Failed"
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([$orderId]);
        self::assertSame(LabelStatusManagementInterface::LABEL_STATUS_FAILED, $labelStatus[$orderId]);
    }
}
