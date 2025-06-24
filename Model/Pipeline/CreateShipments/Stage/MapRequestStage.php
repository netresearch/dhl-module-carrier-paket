<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\Stage;

use Dhl\Paket\Model\Pipeline\CreateShipments\ArtifactsContainer;
use Dhl\Paket\Model\Pipeline\CreateShipments\RequestDataMapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class MapRequestStage implements CreateShipmentsStageInterface
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
     * Transform core shipment requests into request objects suitable for the label API.
     *
     * Requests with mapping errors are removed from requests and instantly added as error responses.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface $artifactsContainer
     * @return Request[]
     */
    #[\Override]
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $callback = function (Request $request, int $requestIndex) use ($artifactsContainer) {
            try {
                $shipmentOrder = $this->requestDataMapper->mapRequest($requestIndex, $request);
                $artifactsContainer->addApiRequest($requestIndex, $shipmentOrder);

                return true;
            } catch (LocalizedException $exception) {
                $artifactsContainer->addError(
                    $requestIndex,
                    $request->getOrderShipment(),
                    $exception->getMessage()
                );
                return false;
            }
        };

        // pass on only the shipment requests that could be mapped
        return array_filter($requests, $callback, ARRAY_FILTER_USE_BOTH);
    }
}
