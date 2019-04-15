<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Processor;

use Dhl\Paket\Model\Cancel\Request as CancelRequest;
use Dhl\Paket\Webservice\CarrierResponse\ErrorResponse;
use Dhl\Paket\Webservice\CarrierResponse\FailureResponse;
use Dhl\Paket\Webservice\CarrierResponse\ShipmentResponse;
use Dhl\ShippingCore\Api\LabelStatusManagementInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class LabelStatusProcessor
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class LabelStatusProcessor implements OperationProcessorInterface
{
    /**
     * @var LabelStatusManagementInterface
     */
    private $labelStatusManagement;

    /**
     * LabelStatusProcessor constructor.
     *
     * @param LabelStatusManagementInterface $labelStatusManagement
     */
    public function __construct(LabelStatusManagementInterface $labelStatusManagement)
    {
        $this->labelStatusManagement = $labelStatusManagement;
    }

    /**
     * Set label status to order according to response type.
     *
     * @param DataObject|ShipmentResponse|ErrorResponse|FailureResponse $shipmentResponse
     * @param Order $order
     */
    private function setLabelStatus(DataObject $shipmentResponse, Order $order)
    {
        if ($shipmentResponse instanceof ErrorResponse || $shipmentResponse instanceof FailureResponse) {
            $this->labelStatusManagement->setLabelStatusFailed($order);
        } elseif ($order->canShip()) {
            $this->labelStatusManagement->setLabelStatusPending($order);
        } else {
            $this->labelStatusManagement->setLabelStatusProcessed($order);
        }
    }

    /**
     * Mark orders' label status according to the webservice operation result.
     *
     * @param Request[] $shipmentRequests
     * @param ShipmentResponse[]|ErrorResponse[]|FailureResponse[] $shipmentResponses
     */
    public function processCreateShipmentsResponse(array $shipmentRequests, array $shipmentResponses)
    {
        if (count($shipmentResponses) === 1 && ($shipmentResponses[0] instanceof FailureResponse)) {
            // set failure status to all orders
            array_walk(
                $shipmentRequests,
                function (Request $request) use ($shipmentResponses) {
                    $this->setLabelStatus($shipmentResponses[0], $request->getOrderShipment()->getOrder());
                }
            );
        } else {
            // set status per order
            array_walk(
                $shipmentResponses,
                function (DataObject $response) use ($shipmentRequests) {
                    /** @var ShipmentResponse|ErrorResponse $response */
                    $request = $shipmentRequests[$response->getSequenceNumber()];
                    $this->setLabelStatus($response, $request->getOrderShipment()->getOrder());
                }
            );
        }
    }

    /**
     * Mark orders with cancelled shipments "pending".
     *
     * @param CancelRequest[] $requested Shipment cancellation requests
     * @param string[] $cancelled Shipment numbers cancelled successfully.
     */
    public function processCancelShipmentsResponse(array $requested, array $cancelled)
    {
        foreach ($requested as $cancelRequest) {
            if (\in_array($cancelRequest->getTrackId(), $cancelled)) {
                $this->labelStatusManagement->setLabelStatusPending($cancelRequest->getOrder());
            }
        }
    }
}
