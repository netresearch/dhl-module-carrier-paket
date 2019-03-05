<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Exception\AuthenticationException;
use Dhl\Sdk\Paket\Bcs\Exception\ClientException;
use Dhl\Sdk\Paket\Bcs\Exception\ServerException;

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
     * @return ShipmentInterface[]
     *
     * @throws AuthenticationException
     * @throws ClientException
     * @throws ServerException
     */
    public function performShipmentOrderRequest($request): array;
}
