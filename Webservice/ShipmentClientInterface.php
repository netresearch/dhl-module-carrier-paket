<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Sdk\Bcs\Api\Data\CreateShipmentOrderResponseInterface;
use Dhl\Sdk\Bcs\Api\Data\ShipmentRequestInterface;
use Dhl\Sdk\Bcs\Webservice\Exception\AuthenticationException;
use Dhl\Sdk\Bcs\Webservice\Exception\GeneralErrorException;
use Dhl\Sdk\Bcs\Webservice\Exception\HardValidationException;
use Dhl\Sdk\Bcs\Webservice\Exception\ServiceUnavailableException;
use Dhl\Sdk\Bcs\Webservice\Exception\SoapException;
use Dhl\Sdk\Bcs\Webservice\Exception\UnknownShipmentNumberException;
use Dhl\Sdk\Bcs\Webservice\Exception\WeakValidationException;

/**
 * The shipment client that directly uses the SDK services/service factories.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.netresearch.de/
 */
interface ShipmentClientInterface
{
    /**
     * Performs the create shipment order request and returns the response.
     *
     * @param object $request The shipment request
     *
     * @return CreateShipmentOrderResponseInterface
     *
     * @throws AuthenticationException
     * @throws GeneralErrorException
     * @throws HardValidationException
     * @throws ServiceUnavailableException
     * @throws UnknownShipmentNumberException
     * @throws WeakValidationException
     * @throws SoapException
     */
    public function performShipmentOrderRequest(ShipmentRequestInterface $request): CreateShipmentOrderResponseInterface;
}
