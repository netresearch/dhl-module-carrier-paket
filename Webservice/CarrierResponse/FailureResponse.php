<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\CarrierResponse;

use Magento\Framework\DataObject;

/**
 * FailureResponse
 *
 * The response type consumed by the core carrier.
 * Overall webservice failure. Not related to particular shipment request.
 *
 * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class FailureResponse extends DataObject
{
    /**
     * Get tracking number from response.
     *
     * @return string
     */
    public function getErrors()
    {
        return $this->getData('errors');
    }
}
