<?php
/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Test\Integration\TestDouble\ShipmentServiceStub;
use Dhl\Paket\Webservice\ApiGateway;
use Dhl\Paket\Webservice\ApiGatewayFactory;
use Dhl\Paket\Webservice\ShipmentServiceFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Psr\Log\Test\TestLogger;

/**
 * Base test to build various shipment creation scenarios on.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 *
 * @package Dhl\Paket\Test\Integration
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
abstract class SaveShipmentTest extends AbstractBackendController
{
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
    protected $uri = 'backend/admin/order_shipment/save';

    /**
     * @var string
     */
    protected $httpMethod = 'POST';

    /**
     * @var TestLogger
     */
    protected $logger;

    /**
     * @var ShipmentServiceStub
     */
    protected $shipmentService;

    /**
     * @var OrderInterface|Order
     */
    protected static $order;

    /**
     * Set up the shipment service stub to suppress actual api calls.
     *
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    protected function setUp()
    {
        parent::setUp();

        $this->logger = new TestLogger();
        $this->shipmentService = new ShipmentServiceStub();
        $serviceFactoryMock = $this->getMockBuilder(ShipmentServiceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $serviceFactoryMock->method('create')->willReturn($this->shipmentService);

        $apiGateway = Bootstrap::getObjectManager()->create(ApiGateway::class, [
            'logger' => $this->logger,
            'serviceFactory' => $serviceFactoryMock,
        ]);
        $apiGatewayFactoryMock = $this->getMockBuilder(ApiGatewayFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $apiGatewayFactoryMock->method('create')->willReturn($apiGateway);

        Bootstrap::getObjectManager()->addSharedInstance($apiGatewayFactoryMock, ApiGatewayFactory::class);
    }

    /**
     * Run request.
     *
     * Set form key if not available (required for Magento < 2.2.8).
     *
     * @link https://github.com/magento/magento2/blob/2.2.7/dev/tests/integration/framework/Magento/TestFramework/TestCase/AbstractController.php#L100
     * @link https://github.com/magento/magento2/blob/2.2.8/dev/tests/integration/framework/Magento/TestFramework/TestCase/AbstractController.php#L109-L116
     * @param string $uri
     */
    public function dispatch($uri)
    {
        if (!array_key_exists('form_key', $this->getRequest()->getPost())) {
            /** @var FormKey $formKey */
            $formKey = $this->_objectManager->get(FormKey::class);
            $this->getRequest()->setPostValue('form_key', $formKey->getFormKey());
        }

        parent::dispatch($uri);
    }

    /**
     * Create order fixture.
     */
    abstract public static function createOrder();

    /**
     * Assert that label requests are properly processed to the carrier api and back.
     */
    abstract public function saveShipment();

    /**
     * @test
     * @magentoDataFixture createOrder
     *
     * @magentoConfigFixture default_store catalog/price/scope 0
     * @magentoConfigFixture default_store currency/options/base EUR
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
        $this->getRequest()->setParam('order_id', self::$order->getEntityId());

        parent::testAclHasAccess();
    }
}
