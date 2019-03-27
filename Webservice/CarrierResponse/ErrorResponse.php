<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\CarrierResponse;

use Magento\Framework\DataObject;

/**
 * ShipmentResponse
 *
 * The response type consumed by the core carrier.
 * Error related to a particular shipment request, identified by sequence number.
 *
 * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ErrorResponse extends DataObject
{
    /**
     * Get sequence number from response.
     *
     * Sequence number is used to associate request-response pairs.
     *
     * @return string
     */
    public function getSequenceNumber()
    {
        return $this->getData('sequence_number');
    }

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
