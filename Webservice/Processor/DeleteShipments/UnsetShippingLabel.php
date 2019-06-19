<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Processor\DeleteShipments;

use Dhl\ShippingCore\Api\Data\TrackResponse\TrackErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Api\TrackResponseProcessorInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment;

/**
 * Class UnsetShippingLabel
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class UnsetShippingLabel implements TrackResponseProcessorInterface
{
    /**
     * @var Shipment
     */
    private $shipmentResource;

    /**
     * UnsetShippingLabel constructor.
     * @param Shipment $shipmentResource
     */
    public function __construct(Shipment $shipmentResource)
    {
        $this->shipmentResource = $shipmentResource;
    }

    /**
     * Collect shipments where some tracks could not be cancelled.
     *
     * @param TrackErrorResponseInterface[] $errorResponses Shipment cancellation errors
     * @return ShipmentInterface[]
     */
    private function getFailedShipments(array $errorResponses)
    {
        $failedShipments = [];

        foreach ($errorResponses as $errorResponse) {
            $shipment = $errorResponse->getSalesShipment();
            if ($shipment !== null) {
                $failedShipments[$shipment->getEntityId()] = $shipment;
            }
        }

        return $failedShipments;
    }

    /**
     * Collect shipments where some tracks were cancelled.
     *
     * @param TrackResponseInterface[] $trackResponses Shipment cancellation responses
     * @return ShipmentInterface[]
     */
    private function getCancelledShipments(array $trackResponses)
    {
        $cancelledShipments = [];

        foreach ($trackResponses as $trackResponse) {
            $shipment = $trackResponse->getSalesShipment();
            if ($shipment !== null) {
                $cancelledShipments[$shipment->getEntityId()] = $shipment;
            }
        }

        return $cancelledShipments;
    }

    /**
     * Delete label properties for successfully cancelled shipments.
     *
     * @param TrackResponseInterface[] $trackResponses Shipment cancellation responses
     * @param TrackErrorResponseInterface[] $errorResponses Shipment cancellation errors
     */
    public function processResponse(array $trackResponses, array $errorResponses)
    {
        $cancelledShipments = $this->getCancelledShipments($trackResponses);
        $failedShipments = $this->getFailedShipments($errorResponses);

        // collect shipments which had no errors, unset labels
        $shipments = array_diff_key($cancelledShipments, $failedShipments);
        array_walk($shipments, function (ShipmentInterface $shipment) {
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment->setShippingLabel(null);
            $this->shipmentResource->save($shipment);
        });
    }
}
