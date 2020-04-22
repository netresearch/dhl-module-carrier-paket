<?php
/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Order\Shipment;

use Dhl\Paket\Model\Pipeline\CreateShipments\Stage\SendRequestStage as CreationStage;
use Dhl\Paket\Model\Pipeline\DeleteShipments\Stage\SendRequestStage as CancellationStage;
use Dhl\Paket\Test\Integration\Provider\Controller\SaveShipment\PostDataProvider;
use Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\ControllerTest;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub as CreationStageStub;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub as CancellationStageStub;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * Base test to build various shipment creation scenarios on.
 *
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
abstract class SaveShipmentTest extends ControllerTest
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
    protected $uri = 'backend/dhl/order_shipment/save';

    /**
     * The order to create the shipment request for.
     *
     * @var OrderInterface|Order
     */
    protected static $order;

    /**
     * The actual test to be implemented.
     *
     * @param callable $getPostData
     */
    abstract public function saveShipment(callable $getPostData);

    /**
     * Configure pipeline stage for shipment creations.
     *
     * @throws AuthenticationException
     */
    protected function setUp()
    {
        parent::setUp();

        // configure positive web service response
        $this->_objectManager->configure(
            [
                'preferences' => [
                    CreationStage::class => CreationStageStub::class,
                    CancellationStage::class => CancellationStageStub::class,
                ],
            ]
        );
    }

    public function postDataProvider()
    {
        return [
            'single_package' => [
                function () {
                    return PostDataProvider::singlePackageDomestic(self::$order);
                },
            ],
            'multi_package' => [
                function () {
                    return PostDataProvider::multiPackageDomestic(self::$order);
                },
            ],
        ];
    }

    /**
     * Run request.
     *
     * Set form key if not available (required for Magento < 2.2.8).
     *
     * @link https://github.com/magento/magento2/blob/2.2.7/dev/tests/integration/framework/Magento/TestFramework/TestCase/AbstractController.php#L100
     * @link https://github.com/magento/magento2/blob/2.2.8/dev/tests/integration/framework/Magento/TestFramework/TestCase/AbstractController.php#L109-L116
     *
     * @param string $uri
     * @throws LocalizedException
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
     * @magentoConfigFixture default_store catalog/price/scope 0
     * @magentoConfigFixture default_store currency/options/base EUR
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function testAclHasAccess()
    {
        $this->getRequest()->setParam('order_id', '123456789');
        $this->getRequest()->setParam('data', '[]');

        parent::testAclHasAccess();
    }
}
