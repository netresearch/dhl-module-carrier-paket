<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\DeleteShipments;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackResponseInterface;

class ArtifactsContainer implements ArtifactsContainerInterface
{
    /**
     * Store id the pipeline runs for.
     *
     * @var int|null
     */
    private $storeId;

    /**
     * Error messages occurred during pipeline execution.
     *
     * @var string[][]|ShipmentInterface[][]|ShipmentTrackInterface[][]
     */
    private $errors = [];

    /**
     * API (SDK) request items.
     *
     * @var string[]
     */
    private $apiRequests = [];

    /**
     * API (SDK) response items.
     *
     * @var string[]
     */
    private $apiResponses = [];

    /**
     * Label response suitable for processing by the core.
     *
     * @var TrackResponseInterface[]
     */
    private $trackResponses = [];

    /**
     * Error response suitable for processing by the core. Contains request id / tracking number.
     *
     * @var TrackErrorResponseInterface[]
     */
    private $errorResponses = [];

    /**
     * Set store id for the pipeline.
     *
     * @param int $storeId
     * @return void
     */
    #[\Override]
    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Add error message for a shipment cancellation request.
     *
     * Text errors must only be added if the web service call did not return
     * a response for the particular request item. For errors returned from the
     * web service, use an error object.
     *
     * @see addErrorResponse
     *
     * @param string $requestIndex
     * @param ShipmentInterface|null $shipment
     * @param ShipmentTrackInterface|null $track
     * @param string $errorMessage
     * @return void
     */
    public function addError(
        string $requestIndex,
        ?ShipmentInterface $shipment,
        ?ShipmentTrackInterface $track,
        string $errorMessage
    ) {
        $this->errors[$requestIndex] = [
            'shipment' => $shipment,
            'track' => $track,
            'message' => $errorMessage,
        ];
    }

    /**
     * Add a shipment number for the web service call.
     *
     * @param string $requestIndex
     * @param string $shipmentNumber
     * @return void
     */
    public function addApiRequest(string $requestIndex, string $shipmentNumber)
    {
        $this->apiRequests[$requestIndex] = $shipmentNumber;
    }

    /**
     * Add a received response, a successfully cancelled shipment number.
     *
     * @param string $requestIndex
     * @param string $shipmentNumber
     * @return void
     */
    public function addApiResponse(string $requestIndex, string $shipmentNumber)
    {
        $this->apiResponses[$requestIndex] = $shipmentNumber;
    }

    /**
     * Add positive label response.
     *
     * @param string $requestIndex
     * @param TrackResponseInterface $trackResponse
     * @return void
     */
    public function addTrackResponse(string $requestIndex, TrackResponseInterface $trackResponse)
    {
        $this->trackResponses[$requestIndex] = $trackResponse;
    }

    /**
     * Add cancellation error.
     *
     * @param string $requestIndex
     * @param TrackErrorResponseInterface $errorResponse
     * @return void
     */
    public function addErrorResponse(string $requestIndex, TrackErrorResponseInterface $errorResponse)
    {
        $this->errorResponses[$requestIndex] = $errorResponse;
    }

    /**
     * Get store id for the pipeline.
     *
     * @return int
     */
    #[\Override]
    public function getStoreId(): int
    {
        return (int) $this->storeId;
    }

    /**
     * Obtain the error messages which occurred during pipeline execution.
     *
     * @return ShipmentInterface[][]|ShipmentTrackInterface[][]|\string[][]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtain the shipment numbers for the web service call.
     *
     * @return string[]
     */
    public function getApiRequests(): array
    {
        return $this->apiRequests;
    }

    /**
     * Obtain the successfully cancelled shipment numbers.
     *
     * @return string[]
     */
    public function getApiResponses(): array
    {
        return $this->apiResponses;
    }

    /**
     * Obtain the tracks cancelled at the web service.
     *
     * @return TrackResponseInterface[]
     */
    public function getTrackResponses(): array
    {
        return $this->trackResponses;
    }

    /**
     * Obtain the cancellation errors occurred during web service call.
     *
     * @return TrackErrorResponseInterface[]
     */
    public function getErrorResponses(): array
    {
        return $this->errorResponses;
    }
}
