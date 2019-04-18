<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Processor;

use Dhl\Paket\Model\Shipment\CancelRequest;
use Dhl\Paket\Webservice\CarrierResponse\ErrorResponse;
use Dhl\Paket\Webservice\CarrierResponse\FailureResponse;
use Dhl\Paket\Webservice\CarrierResponse\ShipmentResponse;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Interface OperationProcessorInterface
 *
 * Perform arbitrary actions after api calls.
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
interface OperationProcessorInterface
{
    /**
     * Perform actions after receiving the "create shipments" response.
     *
     * @param Request[] $shipmentRequests
     * @param ShipmentResponse[]|ErrorResponse[]|FailureResponse[] $shipmentResponses
     */
    public function processCreateShipmentsResponse(array $shipmentRequests, array $shipmentResponses);

    /**
     * Perform actions after receiving the "delete shipments" response.
     *
     * @param CancelRequest[] $cancelRequests Shipment cancellation requests
     * @param string[] $cancelled Shipment numbers cancelled successfully.
     */
    public function processCancelShipmentsResponse(array $cancelRequests, array $cancelled);
}
