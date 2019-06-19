<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Shipment;

use Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\ControllerTest;
use Magento\Framework\Data\Form\FormKey;

/**
 * Class AutoCreateTest
 *
 * Base controller test for the auto-create route.
 *
 * @package Dhl\Paket\Test\Integration\Controller
 */
abstract class AutoCreateTest extends ControllerTest
{
    /**
     * The resource used to authorize action
     *
     * @var string
     */
    protected $resource = 'Magento_Sales::ship';

    /**
     * The uri at which to access the controller
     *
     * @var string
     */
    protected $uri = 'backend/dhl/shipment/autocreate';

    /**
     * The actual test to be implemented.
     */
    abstract public function createLabels();

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
     * @magentoConfigFixture default/dhlshippingsolutions/dhlglobalwebservices/retry_failed_shipments 0
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
     * @magentoConfigFixture default_store catalog/price/scope 0
     * @magentoConfigFixture default_store currency/options/base EUR
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store carriers/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function testAclHasAccess()
    {
        $postData = [
            'selected' => ['123456789', '987654321'],
            'namespace' => 'sales_order_grid'
        ];
        $this->getRequest()->setPostValue($postData);

        parent::testAclHasAccess();
    }
}
