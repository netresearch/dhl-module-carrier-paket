<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Cancel;

use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * Class Request
 *
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link http://www.netresearch.de/
 */
class Request extends AbstractSimpleObject
{
    const TRACK_ID = 'track_id';
    const SHIPMENT = 'shipment';
    const ORDER = 'order';

    /**
     * @return string
     */
    public function getTrackId(): string
    {
        return (string) $this->_get(self::TRACK_ID);
    }

    /**
     * @return ShipmentInterface
     */
    public function getShipment()
    {
        return $this->_get(self::SHIPMENT);
    }

    /**
     * @return OrderInterface
     */
    public function getOrder()
    {
        return $this->_get(self::ORDER);
    }
}
