<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Model\Shipping;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Test\Integration\Provider\CreateLabelTestProvider;
use Dhl\Paket\Webservice\ApiGatewayFactory;
use Dhl\Paket\Webservice\CarrierResponse\ShipmentResponse;
use Dhl\Paket\Webservice\CarrierResponse\ShipmentResponseFactory;
use Dhl\Paket\Webservice\Processor\OperationProcessorInterface;
use Dhl\Paket\Webservice\Shipment\RequestDataMapper;
use Dhl\Paket\Webservice\Shipment\ResponseDataMapper;
use Dhl\Paket\Webservice\ShipmentServiceFactory;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Shipping\Model\Shipping\Labels;
use Magento\Shipping\Model\Shipping\LabelsFactory;
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\Helper\Bootstrap as BootstrapHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Catalog\ProductFixture;
use TddWizard\Fixtures\Catalog\ProductFixtureRollback;
use TddWizard\Fixtures\Checkout\CartBuilder;
use TddWizard\Fixtures\Checkout\CustomerCheckout;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Customer\CustomerFixture;
use TddWizard\Fixtures\Customer\CustomerFixtureRollback;

/**
 * Class CreateLabelTest
 *
 * @package Dhl\Paket\Test
 */
class CreateLabelTest extends TestCase
{
    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Session
     */
    private $authSession;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @throws AuthenticationException
     */
    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = BootstrapHelper::getObjectManager();

        // Set "adminhtml"
        $this->objectManager->get(ScopeInterface::class)
            ->setCurrentScope(FrontNameResolver::AREA_CODE);

        $this->auth        = $this->objectManager->create(Auth::class);
        $this->authSession = $this->objectManager->create(Session::class);

        $this->auth->setAuthStorage($this->authSession);
        $this->auth->logout();
    }

    /**
     *
     */
    protected function tearDown()
    {
        $this->auth = null;
        $this->objectManager->get(ScopeInterface::class)->setCurrentScope(null);
    }

    /**
     * @throws AuthenticationException
     */
    private function doAdminLogin()
    {
        $this->auth->login(
            Bootstrap::ADMIN_NAME,
            Bootstrap::ADMIN_PASSWORD
        );
    }

    /**
     * @return OrderInterface
     * @throws \Exception
     */
    private function getOrder(): OrderInterface
    {
        $productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()
                ->withPrice(45.0)
                ->withWeight(1.0)
                ->withCustomAttributes(
                    [
                        'dhl_tariff_number' => 123,
                    ]
                )
                ->build()
        );

        $shippingAddressBuilder = AddressBuilder::anAddress()
            ->withFirstname('Max')
            ->withLastname('Mustermann')
            ->withCompany(null)
            ->withCountryId('DE')
            ->withRegionId(91) // Sachsen
            ->withCity('Leipzig')
            ->withPostcode('04229')
            ->withStreet('Nonnenstraße 11d');

        $customerBuilder = CustomerBuilder::aCustomer()
            ->withFirstname('Max')
            ->withLastname('Mustermann')
            ->withAddresses(
                $shippingAddressBuilder->asDefaultBilling(),
                $shippingAddressBuilder->asDefaultShipping()
            )
            ->build();

        $customerFixture = new CustomerFixture($customerBuilder);
        $customerFixture->login();

        $checkout = CustomerCheckout::fromCart(
            CartBuilder::forCurrentSession()
                ->withSimpleProduct(
                    $productFixture->getSku()
                )
                ->build()
        );

        $order = $checkout
            ->withShippingMethodCode('dhlpaket_flatrate')
            ->placeOrder();

        CustomerFixtureRollback::create()->execute($customerFixture);
        ProductFixtureRollback::create()->execute($productFixture);

        return $order;
    }

    /**
     * Provide order and packaging popup request data.
     *
     * @return mixed[]
     * @throws \Exception
     *
     * @todo(nr): use order and request data from fixture.
     * @todo(nr): this will get lengthy, move to separate provider class.
     */
    public function domesticShipmentDataProvider()
    {
        $order = $this->getOrder();

        $shipmentDataDe = [
            'items' => [
                // orderItemId => qty
                124 => 1,
            ],
            'create_shipping_label' => '1',
        ];
        $packagesDataDe = [
            1 => [
                'params' => [
                    'container' => 'V01PAK',
                    'weight' => 0.9,
                    'customs_value' => 55,
                    'length' => 1.0,
                    'width' => 1.0,
                    'height' => 1.0,
                    'weight_units' => 'KILOGRAM',
                    'dimension_units' => 'CENTIMETER',
                    'content_type' => '',
                    'content_type_other' => '',
                ],
                'items' => [
                    124 => [
                        'qty' => 1,
                        'customs_value' => 55.0,
                        'price' => 55.0,
                        'name' => 'Cruise Dual Analog Watch',
                        'weight' => '',
                        'product_id' => 22,
                        'order_item_id' => 124,
                    ],
                ],
            ],
        ];

        $requests = [
            'de-de' => [$order, $shipmentDataDe, $packagesDataDe],
        ];

        return [$order, $shipmentDataDe, $packagesDataDe];
    }

    /**
     * @param int $sequenceNumber
     * @param string $trackingNumber
     * @param string $labelContent
     *
     * @return ShipmentResponse
     */
    private function getShipmentResponse(
        int $sequenceNumber, string $trackingNumber, string $labelContent
    ): ShipmentResponse {
        $shipmentResponseFactory = $this->objectManager->create(ShipmentResponseFactory::class);

        return $shipmentResponseFactory->create(
            ['data' => [
                'sequence_number'        => $sequenceNumber,
                'tracking_number'        => $trackingNumber,
                'shipping_label_content' => $labelContent,
            ]
        ]);
    }

    /**
     * Test the leanest use case: Domestic shipments with no extras.
     *
     * todo(nr): mock SDK access. test focus is the correct transformation of POST data into request builder args.
     *
     * @param OrderInterface $order The order to request a shipping label for
     * @param string[] $shipmentData The shipment data coming from the packaging popup (POST['shipment']).
     * @param string[] $packagesData The packages data coming from the packaging popup (POST['packages']).
     *
     * @test
     *
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
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
     *
     * @return void
     * @throws \Exception
     */
    public function domesticShipment()
    {
        self::markTestSkipped('moved to controller test');

        $this->doAdminLogin();

        list($order, $shipmentData, $packagesData) = $this->domesticShipmentDataProvider();

        /** @var Http $request */
        $request = $this->objectManager->create(Http::class);
        $request->setPostValue('shipment', $shipmentData);
        $request->setPostValue('packages', $packagesData);

        $shipmentLoader = $this->objectManager->create(ShipmentLoader::class);
        $shipmentLoader->setOrderId($order->getEntityId());
        $shipmentLoader->setShipment($shipmentData);

        /** @var Shipment $shipment */
        $shipment = $shipmentLoader->load();
        $shipment->register();

        self::assertEmpty($shipment->getShippingLabel());

        $responseDataMapperMock = $this->getMockBuilder(ResponseDataMapper::class)
            ->setMethods(['createShipmentResponse', 'createErrorResponse', 'createFailureResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $apiGatewayMock = $this->getMockBuilder(\Dhl\Paket\Webservice\ApiGateway::class)
            ->setMethods(['handleRequestAndResponse'])
            ->setConstructorArgs([
                'serviceFactory'        => $this->objectManager->create(ShipmentServiceFactory::class),
                'requestDataMapper'     => $this->objectManager->create(RequestDataMapper::class),
                'responseDataMapper'    => $responseDataMapperMock,
                'operationProcessor'    => $this->objectManager->create(OperationProcessorInterface::class),
                'logger'                => $this->objectManager->create(LoggerInterface::class),
            ])
            ->getMock();
        $apiGatewayMock
            ->expects(self::any())
            ->method('handleRequestAndResponse')
            ->willReturn([
                // Create a dummy response
                $this->getShipmentResponse(
                    1,
                    'TRACKING-NUMBER-123',
                    CreateLabelTestProvider::getLabelPdf()
                ),
            ]);

        $apiGatewayFactoryMock = $this->getMockBuilder(ApiGatewayFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $apiGatewayFactoryMock
            ->expects(self::any())
            ->method('create')
            ->willReturn(
                $apiGatewayMock
            );

        $carrierFactoryMock = $this->getMockBuilder(CarrierFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $carrierFactoryMock
            ->expects(self::any())
            ->method('create')
            ->willReturn(
                $this->objectManager->create(Paket::class, [
                    'apiGatewayFactory' => $apiGatewayFactoryMock,
                ])
            );

        $labelFactoryMock = $this->getMockBuilder(LabelsFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $labelFactoryMock
            ->expects(self::any())
            ->method('create')
            ->willReturn(
                $this->objectManager->create(Labels::class, [
                    'carrierFactory' => $carrierFactoryMock,
                ])
            );

        $labelGenerator = $this->objectManager->create(LabelGenerator::class, [
            'carrierFactory' => $carrierFactoryMock,
            'labelFactory'   => $labelFactoryMock,
        ]);

        $labelGenerator->create($shipment, $request);

        self::assertNotEmpty($shipment->getShippingLabel());
        self::assertSame('TRACKING-NUMBER-123', $shipment->getAllTracks()[0]->getNumber());
    }
}
