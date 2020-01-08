<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDouble;

use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub as CreationStage;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub as CancellationStage;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;

/**
 * Class ShipmentServiceStub
 *
 * Return responses on webservice calls which can be predefined via artifacts containers.
 *
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ShipmentServiceStub implements ShipmentServiceInterface
{
    /**
     * @var CreationStage
     */
    private $createShipmentsStage;

    /**
     * @var CancellationStage
     */
    private $deleteShipmentsStage;

    /**
     * ShipmentServiceStub constructor.
     * @param CreationStage $createShipmentsStage
     * @param CancellationStage $deleteShipmentsStage
     */
    public function __construct(
        CreationStage $createShipmentsStage,
        CancellationStage $deleteShipmentsStage
    ) {
        $this->createShipmentsStage = $createShipmentsStage;
        $this->deleteShipmentsStage = $deleteShipmentsStage;
    }

    public function createShipments(array $shipmentOrders): array
    {
        if (isset($this->createShipmentsStage->exception)) {
            throw $this->createShipmentsStage->exception;
        }

        return $this->createShipmentsStage->apiResponses;
    }

    public function cancelShipments(array $shipmentNumbers): array
    {
        if (isset($this->deleteShipmentsStage->exception)) {
            throw $this->deleteShipmentsStage->exception;
        }

        return $this->deleteShipmentsStage->apiResponses;
    }
}
