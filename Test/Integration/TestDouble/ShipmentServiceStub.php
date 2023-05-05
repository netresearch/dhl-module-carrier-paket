<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDouble;

use Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub as CreationStage;
use Dhl\Paket\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub as CancellationStage;
use Dhl\Sdk\Paket\Bcs\Api\Data\OrderConfigurationInterface;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Api\Data\ValidationResultInterface;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;

/**
 * Return responses on webservice calls which can be predefined via artifacts containers.
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
     *
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

    /**
     * Not implemented.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return '';
    }

    /**
     * Not implemented.
     *
     * @param \stdClass[] $shipmentOrders
     * @param OrderConfigurationInterface|null $configuration
     * @return ValidationResultInterface[]
     */
    public function validateShipments(array $shipmentOrders, OrderConfigurationInterface $configuration = null): array
    {
        return [];
    }

    /**
     * Return a fake web service response pre-defined via CreateShipmentsStageInterface
     *
     * @param \stdClass[] $shipmentOrders
     * @param OrderConfigurationInterface|null $configuration
     * @return ShipmentInterface[]
     * @throws ServiceException
     * @see \Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage\SendRequestStageStub
     *
     */
    public function createShipments(array $shipmentOrders, OrderConfigurationInterface $configuration = null): array
    {
        $callback = $this->createShipmentsStage->responseCallback;
        if (is_callable($callback)) {
            // created shipments or exception
            $response = $callback($this->createShipmentsStage);
            if ($response instanceof ServiceException) {
                throw $response;
            }

            if (is_array($response)) {
                return $response;
            }
        }

        // response callback not defined or empty, return default response.
        return $this->createShipmentsStage->apiResponses;
    }

    /**
     * Return a fake web service response pre-defined via RequestTracksStageInterface
     *
     * @param string[] $shipmentNumbers
     * @param string $profile
     * @return string[]
     * @throws ServiceException
     * @see \Dhl\Paket\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage\SendRequestStageStub
     *
     */
    public function cancelShipments(
        array $shipmentNumbers,
        string $profile = OrderConfigurationInterface::DEFAULT_PROFILE
    ): array {
        $callback = $this->deleteShipmentsStage->responseCallback;
        if (is_callable($callback)) {
            // cancelled shipment numbers or exception
            $response = $callback($this->deleteShipmentsStage);
            if ($response instanceof ServiceException) {
                throw $response;
            }

            if (is_array($response)) {
                return $response;
            }
        }

        // response callback not defined or empty, return default response.
        return $this->deleteShipmentsStage->apiResponses;
    }
}
