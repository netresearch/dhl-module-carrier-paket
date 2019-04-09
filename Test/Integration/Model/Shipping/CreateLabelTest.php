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

    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $authSession;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomerFixture
     */
    private $customerFixture;

    /**
     * @var ProductFixture
     */
    private $productFixture;

    protected function setUp()
    {
        parent::setUp();

        $this->shipmentLoader = Bootstrap::getObjectManager()->get(ShipmentLoader::class);
        $this->labelGenerator = Bootstrap::getObjectManager()->get(LabelGenerator::class);

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->objectManager->get(\Magento\Framework\Config\ScopeInterface::class)
            ->setCurrentScope(\Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
        $this->auth = $this->objectManager->create(\Magento\Backend\Model\Auth::class);
        $this->authSession = $this->objectManager->create(\Magento\Backend\Model\Auth\Session::class);
        $this->auth->setAuthStorage($this->authSession);
        $this->auth->logout();
    }

    protected function tearDown()
    {
        $this->auth = null;
        $this->objectManager->get(\Magento\Framework\Config\ScopeInterface::class)->setCurrentScope(null);
    }

    private function getOrder(): OrderInterface
    {
        $this->productFixture = new ProductFixture(
            ProductBuilder::aSimpleProduct()
                ->withPrice(35.0)
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

        $this->customerFixture = new CustomerFixture($customerBuilder);
        $this->customerFixture->login();

        $checkout = CustomerCheckout::fromCart(
            CartBuilder::forCurrentSession()
                ->withSimpleProduct(
                    $this->productFixture->getSku()
                )
                ->build()
        );

        $order = $checkout
            ->withShippingMethodCode('dhlpaket_flatrate')
            ->placeOrder();

        return $order;
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
        $order = $this->getOrder();

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
     * @magentoAppArea adminhtml
     *
     * @param OrderInterface $order The order to request a shipping label for
     * @param string[] $shipmentData The shipment data coming from the packaging popup (POST['shipment']).
     * @param string[] $packagesData The packages data coming from the packaging popup (POST['packages']).
     *
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     * @magentoConfigFixture current_store shipping/origin/region_id 91
     * @magentoConfigFixture current_store shipping/origin/postcode 04229
     * @magentoConfigFixture current_store shipping/origin/city Leipzig
     * @magentoConfigFixture current_store shipping/origin/street_line1 Nonnenstraße 11
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
    public function domesticShipment(OrderInterface $order, array $shipmentData, array $packagesData)
    {
        $this->auth->login(
            \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

//        $this->markTestIncomplete('Pass in order fixture.');

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


        CustomerFixtureRollback::create()->execute($this->customerFixture);
        ProductFixtureRollback::create()->execute($this->productFixture);
    }
}
