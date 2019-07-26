<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Pipeline\DeleteShipments\Stage;

use Dhl\Paket\Webservice\Pipeline\DeleteShipments\ArtifactsContainer;
use Dhl\Paket\Webservice\Pipeline\DeleteShipments\RequestDataMapper;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Pipeline\RequestTracksStageInterface;

/**
 * Class MapRequestStage
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class MapRequestStage implements RequestTracksStageInterface
{
    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    /**
     * MapRequestStage constructor.
     *
     * @param RequestDataMapper $requestDataMapper
     */
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
