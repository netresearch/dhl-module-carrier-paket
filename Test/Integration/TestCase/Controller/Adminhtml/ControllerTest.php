<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Controller\Adminhtml;

use Dhl\Paket\Test\Integration\TestDouble\ShipmentServiceStub;
use Dhl\Paket\Webservice\ShipmentService;
use Magento\Framework\Exception\AuthenticationException;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Class ControllerTest
 *
 * Base controller test for all actions which trigger label api calls for order fixtures:
 * - Create shipment and label for single order
 * - Create shipments and labels for multiple orders (auto-create)
 *
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
     * @throws AuthenticationException
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_objectManager->configure(['preferences' => [ShipmentService::class => ShipmentServiceStub::class]]);
    }
}
