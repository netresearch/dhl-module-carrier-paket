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
use Dhl\Sdk\Paket\Bcs\Model\CreateShipment\RequestType\ShipmentOrderType;
use Magento\Store\Api\Data\StoreInterface;

/**
 * The shipment client that directly uses the SDK services/service factories.
 *
 * @deprecated
 * @see \Dhl\Paket\Model\Carrier\ApiGateway
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
interface ShipmentClientInterface
{
    /**
     * Performs the create shipment order request and returns the response.
     *
     * @param ShipmentOrderType $shipmentOrder The shipment order request
     * @param StoreInterface    $store
     *
     * @return ShipmentInterface[]
     *
     * @throws AuthenticationException
     * @throws ClientException
     * @throws ServerException
     */
    public function performShipmentOrderRequest(
        ShipmentOrderType $shipmentOrder,
        StoreInterface $store
    ): array;
}
