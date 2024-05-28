<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments;

use Dhl\Sdk\ParcelDe\Shipping\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;

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
     * @var string[][]|\Magento\Sales\Api\Data\ShipmentInterface[][]
     */
    private $errors = [];

    /**
     * API (SDK) request objects.
     *
     * @var object[]
     */
    private $apiRequests = [];

    /**
     * API (SDK) response objects.
     *
     * @var ShipmentInterface[]
     */
    private $apiResponses = [];

    /**
     * Label response suitable for processing by the core.
     *
     * @var LabelResponseInterface[]
     */
    private $labelResponses = [];

    /**
     * Error response suitable for processing by the core. Contains request id / sequence number.
     *
     * @var ShipmentErrorResponseInterface[]
     */
    private $errorResponses = [];

    /**
     * Set store id for the pipeline.
     *
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Add error message for a shipment request.
     *
     * Text errors must only be added if the web service call did not return
     * a response for the particular request item. For errors returned from the
     * web service, use an error object.
     *
     * @see addErrorResponse
     *
     * @param int $requestIndex
     * @param Shipment $shipment
     * @param string $errorMessage
     * @return void
     */
    public function addError(int $requestIndex, Shipment $shipment, string $errorMessage)
    {
        $this->errors[$requestIndex] = [
            'shipment' => $shipment,
            'message' => $errorMessage,
        ];
    }

    /**
     * Add a prepared request object, ready for the web service call.
     *
     * @param int $requestIndex
     * @param object $shipmentOrder
     * @return void
     */
    public function addApiRequest(int $requestIndex, $shipmentOrder)
    {
        $this->apiRequests[$requestIndex] = $shipmentOrder;
    }

    /**
     * Add a received response object.
     *
     * @param int $requestIndex
     * @param ShipmentInterface $apiResponse
     * @return void
     */
    public function addApiResponse(int $requestIndex, ShipmentInterface $apiResponse)
    {
        $this->apiResponses[$requestIndex] = $apiResponse;
    }

    /**
     * Add positive label response.
     *
     * @param string $requestIndex
     * @param LabelResponseInterface $labelResponse
     * @return void
     */
    public function addLabelResponse(int $requestIndex, LabelResponseInterface $labelResponse)
    {
        $this->labelResponses[$requestIndex] = $labelResponse;
    }

    /**
     * Add label error.
     *
     * @param int $requestIndex
     * @param ShipmentErrorResponseInterface $errorResponse
     * @return void
     */
    public function addErrorResponse(int $requestIndex, ShipmentErrorResponseInterface $errorResponse)
    {
        $this->errorResponses[$requestIndex] = $errorResponse;
    }

    /**
     * Get store id for the pipeline.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return (int) $this->storeId;
    }

    /**
     * Obtain the error messages which occurred during pipeline execution.
     *
     * @return \Magento\Sales\Api\Data\ShipmentInterface[][]|string[][]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtain the prepared request objects, ready for the web service call.
     *
     * @return object[]
     */
    public function getApiRequests(): array
    {
        return $this->apiRequests;
    }

    /**
     * Obtain the response objects as received from the web service.
     *
     * @return ShipmentInterface[]
     */
    public function getApiResponses(): array
    {
        return $this->apiResponses;
    }

    /**
     * Obtain the labels retrieved from the web service.
     *
     * @return LabelResponseInterface[]
     */
    public function getLabelResponses(): array
    {
        return $this->labelResponses;
    }

    /**
     * Obtain the label errors occurred during web service call.
     *
     * @return ShipmentErrorResponseInterface[]
     */
    public function getErrorResponses(): array
    {
        return $this->errorResponses;
    }
}
