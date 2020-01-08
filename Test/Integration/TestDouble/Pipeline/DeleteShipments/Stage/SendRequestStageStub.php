<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage;

use Dhl\Paket\Webservice\Pipeline\DeleteShipments\ArtifactsContainer;
use Dhl\Paket\Webservice\Pipeline\DeleteShipments\Stage\SendRequestStage;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterface;

/**
 * Class SendRequestStageStub
 *
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class SendRequestStageStub extends SendRequestStage
{
    /**
     * Service exception. Can be set to make the request fail.
     *
     * @var ServiceException
     */
    public $exception;

    /**
     * Regular API responses. Built during runtime from the given cancellation requests.
     *
     * @var ShipmentInterface[]
     */
    public $apiResponses = [];

    /**
     * Send request data to shipment service.
     *
     * @param TrackRequestInterface[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return TrackRequestInterface[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        foreach ($requests as $deletionRequest) {
            $shipmentNumber = $deletionRequest->getTrackNumber();
            $this->apiResponses[$shipmentNumber] = $shipmentNumber;
        }

        return parent::execute($requests, $artifactsContainer);
    }
}
