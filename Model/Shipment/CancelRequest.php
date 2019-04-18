<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;

/**
 * Class CancelRequest
 *
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link https://www.netresearch.de/
 */
class CancelRequest
{
    /**
     * @var ShipmentTrackInterface
     */
    private $track;

    /**
     * @var ShipmentInterface
     */
    private $shipment;

    /**
     * CancelRequest constructor.
     *
     * @param ShipmentTrackInterface $track
     * @param ShipmentInterface $shipment
     */
    public function __construct(ShipmentTrackInterface $track, ShipmentInterface $shipment)
    {
        $this->track = $track;
        $this->shipment = $shipment;
    }

    /**
     * @return ShipmentTrackInterface
     */
    public function getTrack(): ShipmentTrackInterface
    {
        return $this->track;
    }

    /**
     * @return ShipmentInterface
     */
    public function getShipment(): ShipmentInterface
    {
        return $this->shipment;
    }
}
