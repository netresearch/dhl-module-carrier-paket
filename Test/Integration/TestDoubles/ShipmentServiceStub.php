<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDoubles;

use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;

/**
 * Class ShipmentServiceStub
 *
 * Return predefined data on webservice calls.
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ShipmentServiceStub implements ShipmentServiceInterface
{
    /**
     * Shipment orders passed to the carrier webservice. Can be used for assertions.
     *
     * @var \stdClass[]
     */
    public $shipmentOrders;

    /**
     * Shipment numbers passed to the carrier webservice. Can be used for assertions.
     *
     * @var string[]
     */
    public $shipmentNumbers;

    /**
     * Shipments returned from the carrier webservice.
     *
     * @var ShipmentInterface[]
     */
    private $createdShipments = [];

    /**
     * Shipments cancelled at the carrier webservice.
     *
     * @var string[]
     */
    private $cancelledShipments = [];

    /**
     * Service exception. Can be set to make the request fail.
     *
     * @var ServiceException
     */
    private $exception;

    /**
     * @param ShipmentInterface[] $shipments
     */
    public function setCreatedShipments(array $shipments)
    {
        $this->createdShipments = $shipments;
    }

    /**
     * @param string[] $shipmentNumbers
     */
    public function setCancelledShipments(array $shipmentNumbers)
    {
        $this->cancelledShipments = $shipmentNumbers;
    }

    /**
     * @param ServiceException $exception
     */
    public function setExceptionResponse(ServiceException $exception)
    {
        $this->exception = $exception;
    }

    /**
     * CreateShipmentOrder is the operation call used to generate shipments with the relevant DHL Paket labels.
     *
     * @param \stdClass[] $shipmentOrders
     * @return ShipmentInterface[]
     * @throws ServiceException
     */
    public function createShipments(array $shipmentOrders): array
    {
        $this->shipmentOrders = $shipmentOrders;

        if (isset($this->exception)) {
            throw $this->exception;
        }

        return $this->createdShipments;
    }

    /**
     * This operation cancels earlier created shipments.
     *
     * @param string[] $shipmentNumbers
     * @return string[]
     * @throws ServiceException
     */
    public function cancelShipments(array $shipmentNumbers): array
    {
        $this->shipmentNumbers = $shipmentNumbers;

        if (isset($this->exception)) {
            throw $this->exception;
        }

        return $this->cancelledShipments;
    }
}
