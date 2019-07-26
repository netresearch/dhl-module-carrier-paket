<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Webservice\Pipeline\CreateShipments\ArtifactsContainer as CreateArtifactsContainer;
use Dhl\Paket\Webservice\Pipeline\DeleteShipments\ArtifactsContainer as DeleteArtifactsContainer;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\ShipmentErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsPipelineInterface;
use Dhl\ShippingCore\Api\Pipeline\RequestTracksPipelineInterface;
use Dhl\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;
use Dhl\ShippingCore\Api\Pipeline\TrackResponseProcessorInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class ApiGateway
 *
 * Magento carrier-aware wrapper around the DHL Paket API SDK.
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ApiGateway
{
    /**
     * @var CreateShipmentsPipelineInterface
     */
    private $creationPipeline;

    /**
     * @var RequestTracksPipelineInterface
     */
    private $deletionPipeline;

    /**
     * @var ShipmentResponseProcessorInterface
     */
    private $createResponseProcessor;

    /**
     * @var TrackResponseProcessorInterface
     */
    private $deleteResponseProcessor;

    /**
     * @var int
     */
    private $storeId;

    /**
     * ApiGateway constructor.
     *
     * @param CreateShipmentsPipelineInterface $creationPipeline
     * @param RequestTracksPipelineInterface $deletionPipeline
     * @param ShipmentResponseProcessorInterface $createResponseProcessor
     * @param TrackResponseProcessorInterface $deleteResponseProcessor
     * @param int $storeId
     */
    public function __construct(
        CreateShipmentsPipelineInterface $creationPipeline,
        RequestTracksPipelineInterface $deletionPipeline,
        ShipmentResponseProcessorInterface $createResponseProcessor,
        TrackResponseProcessorInterface $deleteResponseProcessor,
        int $storeId
    ) {
        $this->creationPipeline = $creationPipeline;
        $this->deletionPipeline = $deletionPipeline;
        $this->createResponseProcessor = $createResponseProcessor;
        $this->deleteResponseProcessor = $deleteResponseProcessor;
        $this->storeId = $storeId;
    }

    /**
     * Convert shipment requests to shipment orders, inform label status management, send to API, return result.
     *
     * The mapped result can be
     * - an array of tracking-label pairs or
     * - an array of errors.
     *
     * Note that the SDK does not return errors per shipment, only accumulated into one exception message.
     *
     * @param Request[] $shipmentRequests
     * @return LabelResponseInterface[]|ShipmentErrorResponseInterface[]
     */
    public function createShipments(array $shipmentRequests): array
    {
        /** @var CreateArtifactsContainer $artifactsContainer */
        $artifactsContainer = $this->creationPipeline->run($this->storeId, $shipmentRequests);

        $this->createResponseProcessor->processResponse(
            $artifactsContainer->getLabelResponses(),
            $artifactsContainer->getErrorResponses()
        );

        return array_merge($artifactsContainer->getErrorResponses(), $artifactsContainer->getLabelResponses());
    }

    /**
     * Send cancellation request to API, inform label status management, return result.
     *
     * @param TrackRequestInterface[] $cancelRequests
     * @return TrackResponseInterface[]
     */
    public function cancelShipments(array $cancelRequests): array
    {
        /** @var DeleteArtifactsContainer $artifactsContainer */
        $artifactsContainer = $this->deletionPipeline->run($this->storeId, $cancelRequests);

        $this->deleteResponseProcessor->processResponse(
            $artifactsContainer->getTrackResponses(),
            $artifactsContainer->getErrorResponses()
        );

        return array_merge($artifactsContainer->getErrorResponses(), $artifactsContainer->getTrackResponses());
    }
}
