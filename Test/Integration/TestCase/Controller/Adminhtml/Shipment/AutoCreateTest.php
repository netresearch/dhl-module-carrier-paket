<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\Shipment;

use Dhl\Paket\Model\Pipeline\CreateShipments\Stage\SendRequestStage;
use Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml\ControllerTest;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub;
use Magento\Framework\Exception\AuthenticationException;

/**
 * Base controller test for the auto-create route.
 *
 * @method \Magento\Framework\App\Request\Http getRequest()
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
    protected $uri = 'backend/nrshipping/shipment/autocreate';

    /**
     * The actual test to be implemented.
     */
    abstract public function createLabels();

    /**
     * Configure pipeline stage for shipment creations.
     *
     * @throws AuthenticationException
     */
    protected function setUp(): void
    {
        parent::setUp();

        // configure web service response
        $this->_objectManager->configure(['preferences' => [SendRequestStage::class => SendRequestStageStub::class]]);
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
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     *
     * @magentoConfigFixture default/shipping/batch_processing/shipping_label/retry_failed_shipments 0
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
