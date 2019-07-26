<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml;

use Dhl\Paket\Test\Integration\TestDouble\ShipmentServiceStub;
use Dhl\Paket\Webservice\ShipmentService;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class AutoCreateTest
 *
 * Base controller test for all actions which trigger label api calls for order fixtures:
 * - Create shipment and label for single order
 * - Create shipments and labels for multiple orders (auto-create)
 *
 * @package Dhl\Paket\Test\Integration\Controller
 */
abstract class ControllerTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $httpMethod = 'POST';

    /**
     * Set up the shipment service stub to suppress actual api calls.
     *
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_objectManager->configure(['preferences' => [ShipmentService::class => ShipmentServiceStub::class]]);
    }
}
