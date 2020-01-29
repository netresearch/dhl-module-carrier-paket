<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDouble\Pipeline\DeleteShipments\Stage;

use Dhl\Paket\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use Dhl\Paket\Model\Pipeline\DeleteShipments\Stage\SendRequestStage;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;

/**
 * Class SendRequestStageStub
 *
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class SendRequestStageStub extends SendRequestStage
{
    /**
     * Track request objects passed to the stage. Can be used for assertions.
     *
     * @var TrackRequestInterface[]
     */
    public $trackRequests = [];

    /**
     * Shipment numbers sent to the web service. Can be used for assertions.
     *
     * @var \string[]
     */
    public $apiRequests = [];

    /**
     * Regular API responses. Built during runtime from the given cancellation requests.
     *
     * @var ShipmentInterface[]
     */
    public $apiResponses = [];

    /**
     * API response callback. Can be used to alter the default response during runtime, e.g. throw an exception.
     *
     * @var callable|null
     */
    public $responseCallback;

    /**
     * Send request data to shipment service.
     *
     * @param TrackRequestInterface[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return TrackRequestInterface[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $this->trackRequests = $requests;
        $this->apiRequests = $artifactsContainer->getApiRequests();
        $this->apiResponses = [];

        foreach ($requests as $deletionRequest) {
            $shipmentNumber = $deletionRequest->getTrackNumber();
            $this->apiResponses[$shipmentNumber] = $shipmentNumber;
        }

        return parent::execute($requests, $artifactsContainer);
    }
}
