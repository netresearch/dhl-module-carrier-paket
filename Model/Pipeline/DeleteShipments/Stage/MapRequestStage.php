<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\DeleteShipments\Stage;

use Dhl\Paket\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use Dhl\Paket\Model\Pipeline\DeleteShipments\RequestDataMapper;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Netresearch\ShippingCore\Api\Pipeline\RequestTracksStageInterface;

class MapRequestStage implements RequestTracksStageInterface
{
    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    public function __construct(RequestDataMapper $requestDataMapper)
    {
        $this->requestDataMapper = $requestDataMapper;
    }

    /**
     * Transform track requests into cancellation request data suitable for the label API.
     *
     * @param TrackRequestInterface[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return TrackRequestInterface[]
     */
    #[\Override]
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $callback = function (TrackRequestInterface $request) use ($artifactsContainer) {
            $shipmentNumber = $this->requestDataMapper->mapRequest($request);
            $artifactsContainer->addApiRequest($shipmentNumber, $shipmentNumber);
        };

        array_walk($requests, $callback);

        return $requests;
    }
}
