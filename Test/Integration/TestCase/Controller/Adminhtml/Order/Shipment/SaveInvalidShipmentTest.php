<?php
/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Pipeline\CreateShipments\Stage\SendRequestStage as CreationStage;
use Dhl\Paket\Model\Pipeline\DeleteShipments\Stage\SendRequestStage as CancellationStage;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub as CreationStageStub;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub as CancellationStageStub;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\ShippingCore\Api\LabelStatus\LabelStatusManagementInterface;
use Dhl\ShippingCore\Model\LabelStatus\LabelStatusProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Sales\OrderBuilder;
use TddWizard\Fixtures\Sales\OrderFixture;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;

/**
 * Test basic shipment creation failure for DE-DE route with no value-added services.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class SaveInvalidShipmentTest extends SaveShipmentTest
{
    /**
     * Create an order fixture for DE recipient address with an invalid item.
     *
     * @throws \Exception
     */
    public static function createInvalidOrder()
    {
        $shippingMethod = Paket::CARRIER_CODE . '_flatrate';
        self::$order = OrderBuilder::anOrder()->withShippingMethod($shippingMethod)->withProducts(
            ProductBuilder::aSimpleProduct(),
            ProductBuilder::aSimpleProduct()->withWeight(33.303)
        )->build();
    }

    /**
     * @throws \Exception
     */
    public static function createInvalidOrderRollback()
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
     * Scenario: Two products are contained in an order, the second has invalid shipping options.
     *
     * - Items are packed into one package:
     * -- Assert that no shipment is created
     * -- Assert that label status is set to "Failed"
     * - Items are packed into two packages:
     * -- Assert that two packages are sent to the `create` endpoint
     * -- Assert that the first package is sent to the `cancel` endpoint (rolled back)
     * -- Assert that no shipment is created
     * -- Assert that label status is set to "Failed"
     *
     * @test
     * @dataProvider postDataProvider
     * @magentoDataFixture createInvalidOrder
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
        $data = $getPostData();
        $createShipmentsCount = 0;
        $cancelShipmentsCount = 0;

        /** @var CreationStageStub $creationStage */
        $creationStage = $this->_objectManager->get(CreationStage::class);
        // create response callback to count service invocation and throw exception on invalid shipping option
        $creationStage->responseCallback = function (CreationStageStub $stage) use (&$createShipmentsCount) {
            $createShipmentsCount++;
            if ($stage->shipmentRequests[0]->getPackageWeight() > 31.5) {
                return new ServiceException('weighty.');
            }

            return null;
        };

        /** @var CancellationStageStub $cancellationStage */
        $cancellationStage = $this->_objectManager->get(CancellationStage::class);
        // create response callback to count service invocation
        $cancellationStage->responseCallback = function () use (&$cancelShipmentsCount) {
            $cancelShipmentsCount++;
            return null;
        };

        // dispatch
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setPostValue('data', \json_encode($data));
        $this->getRequest()->setParam('order_id', self::$order->getEntityId());
        $this->dispatch($this->uri);

        /** @var Collection $shipmentCollection */
        $shipmentCollection = $this->_objectManager->create(Collection::class);
        $shipments = $shipmentCollection->setOrderFilter(self::$order)->getItems();
        $shipments = array_values($shipments);

        // assert that the `create` endpoint was invoked for each package
        self::assertCount($createShipmentsCount, $data['packages']);

        // assert that the `cancel` endpoint was invoked only if two packages were sent
        self::assertCount($cancelShipmentsCount + 1, $data['packages']);

        // assert that no shipments were created for the order
        self::assertEmpty($shipments);

        // assert that the order's label status is "Failed"
        /** @var LabelStatusProvider $labelStatusProvider */
        $labelStatusProvider = $this->_objectManager->create(LabelStatusProvider::class);
        $labelStatus = $labelStatusProvider->getLabelStatus([self::$order->getEntityId()]);
        self::assertSame(
            LabelStatusManagementInterface::LABEL_STATUS_FAILED,
            $labelStatus[self::$order->getEntityId()]
        );
    }
}
