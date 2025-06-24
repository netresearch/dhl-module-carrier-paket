<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\DeleteShipments\Stage;

use Dhl\Paket\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use Dhl\Paket\Model\Webservice\ShipmentServiceFactory;
use Dhl\Sdk\ParcelDe\Shipping\Exception\ServiceException;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Netresearch\ShippingCore\Api\Pipeline\RequestTracksStageInterface;

class SendRequestStage implements RequestTracksStageInterface
{
    /**
     * @var ShipmentServiceFactory
     */
    private $shipmentServiceFactory;

    public function __construct(ShipmentServiceFactory $shipmentServiceFactory)
    {
        $this->shipmentServiceFactory = $shipmentServiceFactory;
    }

    /**
     * Send request data to shipment service.
     *
     * @param TrackRequestInterface[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return TrackRequestInterface[]
     */
    #[\Override]
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $apiRequests = $artifactsContainer->getApiRequests();
        if (!empty($apiRequests)) {
            $shipmentService = $this->shipmentServiceFactory->create(['storeId' => $artifactsContainer->getStoreId()]);

            try {
                $shipmentNumbers = array_values($apiRequests);
                $cancelledShipments = $shipmentService->cancelShipments($shipmentNumbers);
                // add shipment number as response index
                foreach ($cancelledShipments as $shipmentNumber) {
                    $artifactsContainer->addApiResponse($shipmentNumber, $shipmentNumber);
                }

                return $requests;
            } catch (ServiceException $exception) {
                // mark all requests as failed
                foreach ($requests as $cancelRequest) {
                    $artifactsContainer->addError(
                        $cancelRequest->getTrackNumber(),
                        $cancelRequest->getSalesShipment(),
                        $cancelRequest->getSalesTrack(),
                        $exception->getMessage()
                    );
                }
            }
        }

        // no requests passed the stage
        return [];
    }
}
