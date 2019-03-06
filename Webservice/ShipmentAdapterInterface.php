<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Magento\Framework\DataObject;
use Magento\Shipping\Model\Shipment\Request;

/**
 * The shipment adapter that can be used from outside of the "Webservice" namespace.
 *
 * @deprecated
 * @see \Dhl\Paket\Model\Carrier\ApiGateway
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
interface ShipmentAdapterInterface
{
    /**
     * Create the shipment order. Uses the data mappers to convert Magento data into SDK data.
     *
     * @param Request $request The shipment request
     *
     * @return DataObject
     */
    public function getShipmentLabel(Request $request): DataObject;
}
