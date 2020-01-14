<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\DeleteShipments\Stage;

use Dhl\Paket\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use Dhl\Paket\Model\Pipeline\DeleteShipments\ResponseDataMapper;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Pipeline\RequestTracksStageInterface;

/**
 * Class MapResponseStage
 *
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class MapResponseStage implements RequestTracksStageInterface
{
    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    /**
     * MapResponseStage constructor.
     *
     * @param ResponseDataMapper $responseDataMapper
     */
    public function __construct(ResponseDataMapper $responseDataMapper)
    {
        $this->responseDataMapper = $responseDataMapper;
    }

    /**
     * Transform collected results into response objects suitable for processing by the core.
     *
     * @param TrackRequestInterface[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return TrackRequestInterface[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $stageErrors = $artifactsContainer->getErrors();
        $apiResponses = $artifactsContainer->getApiResponses();

        foreach ($stageErrors as $shipmentNumber => $details) {
            // no response received from webservice for particular cancellation request
            $response = $this->responseDataMapper->createErrorResponse(
                (string) $shipmentNumber,
                __('Shipment %1 could not be cancelled: %2', $shipmentNumber, $details['message']),
                $details['shipment'],
                $details['track']
            );
            $artifactsContainer->addErrorResponse((string) $shipmentNumber, $response);
        }

        foreach ($requests as $shipmentNumber => $cancelRequest) {
            if (isset($stageErrors[$shipmentNumber])) {
                // errors from previous stages were already processed above
                continue;
            }

            if (isset($apiResponses[$shipmentNumber])) {
                // positive response received from webservice
                $response = $this->responseDataMapper->createTrackResponse(
                    (string) $shipmentNumber,
                    $cancelRequest->getSalesShipment(),
                    $cancelRequest->getSalesTrack()
                );

                $artifactsContainer->addTrackResponse((string) $shipmentNumber, $response);
            } else {
                // negative response received from webservice, details available in api log
                $response = $this->responseDataMapper->createErrorResponse(
                    (string) $shipmentNumber,
                    __('Shipment %1 could not be cancelled.', $shipmentNumber),
                    $cancelRequest->getSalesShipment(),
                    $cancelRequest->getSalesTrack()
                );

                $artifactsContainer->addErrorResponse((string) $shipmentNumber, $response);
            }
        }

        return $requests;
    }
}
