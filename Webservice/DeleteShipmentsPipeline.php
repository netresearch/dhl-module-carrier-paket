<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Webservice\Track\RequestDataMapper;
use Dhl\Paket\Webservice\Track\ResponseDataMapper;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\LabelResponseInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\ShipmentErrorResponseInterfaceFactory;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;

/**
 * Class DeleteShipmentsPipeline
 *
 * @package Dhl\Paket\Webservice
 */
class DeleteShipmentsPipeline
{
    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    /**
     * @var ShipmentService
     */
    private $shipmentService;

    /**
     * @var LabelResponseInterfaceFactory
     */
    private $shipmentResponseFactory;

    /**
     * @var ShipmentErrorResponseInterfaceFactory
     */
    private $errorResponseFactory;

    /**
     * Cancellation requests.
     *
     * @var TrackRequestInterface[]
     */
    private $cancelRequests;

    /**
     * API (SDK) request items.
     *
     * @var string[]
     */
    private $apiRequests;

    /**
     * API (SDK) response items.
     *
     * @var string[]
     */
    private $apiResponses;

    /**
     * Error messages occurred during pipeline execution.
     *
     * @var string[][]|ShipmentInterface[][]|ShipmentTrackInterface[][]
     */
    private $errors = [];

    /**
     * Label response suitable for processing by the core.
     *
     * @var TrackResponseInterface[]
     */
    private $trackResponses = [];

    /**
     * Error response suitable for processing by the core. Contains request id / sequence number.
     *
     * @var TrackErrorResponseInterface[]
     */
    private $errorResponses = [];

    /**
     * Pipeline constructor.
     *
     * @param RequestDataMapper $requestDataMapper
     * @param ResponseDataMapper $responseDataMapper
     * @param ShipmentService $shipmentService
     * @param LabelResponseInterfaceFactory $shipmentResponseFactory
     * @param ShipmentErrorResponseInterfaceFactory $errorResponseFactory
     * @param TrackRequestInterface[] $cancelRequests
     */
    public function __construct(
        RequestDataMapper $requestDataMapper,
        ResponseDataMapper $responseDataMapper,
        ShipmentService $shipmentService,
        LabelResponseInterfaceFactory $shipmentResponseFactory,
        ShipmentErrorResponseInterfaceFactory $errorResponseFactory,
        array $cancelRequests
    ) {
        $this->requestDataMapper = $requestDataMapper;
        $this->responseDataMapper = $responseDataMapper;
        $this->shipmentService = $shipmentService;
        $this->shipmentResponseFactory = $shipmentResponseFactory;
        $this->errorResponseFactory = $errorResponseFactory;
        $this->cancelRequests = $cancelRequests;
    }

    /**
     * Transform track requests into cancellation request data suitable for the label API.
     *
     * @return $this
     */
    public function map()
    {
        $callback = function (TrackRequestInterface $request) {
            $shipmentNumber = $this->requestDataMapper->mapRequest($request);
            $this->apiRequests[$shipmentNumber] = $shipmentNumber;
        };

        array_walk($this->cancelRequests, $callback);

        return $this;
    }

    /**
     * Send request data to shipment service.
     *
     * @return $this
     */
    public function send()
    {
        try {
            $shipmentNumbers = array_values($this->apiRequests);
            $cancelledShipments = $this->shipmentService->cancelShipments($shipmentNumbers);
            // add shipment number as response index
            foreach ($cancelledShipments as $shipmentNumber) {
                $this->apiResponses[$shipmentNumber] = $shipmentNumber;
            }
        } catch (ServiceException $exception) {
            // mark all requests as failed
            foreach ($this->cancelRequests as $cancelRequest) {
                $this->errors[$cancelRequest->getTrackNumber()] = [
                    'shipment' => $cancelRequest->getSalesShipment(),
                    'track' => $cancelRequest->getSalesTrack(),
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $this;
    }

    /**
     * Transform collected results into response objects suitable for processing by the core.
     *
     * @return $this
     */
    public function mapResponse()
    {
        foreach ($this->errors as $shipmentNumber => $details) {
            // no response received from webservice for particular cancellation request
            $response = $this->responseDataMapper->createErrorResponse(
                (string) $shipmentNumber,
                __('Shipment %1 could not be cancelled: %2.', $shipmentNumber, $details['message']),
                $details['shipment'],
                $details['track']
            );
            $this->errorResponses[$shipmentNumber] = $response;
        }

        foreach ($this->cancelRequests as $shipmentNumber => $cancelRequest) {
            if (isset($this->errors[$shipmentNumber])) {
                // detailed error message was already processed
                continue;
            }

            if (isset($this->apiResponses[$shipmentNumber])) {
                // positive response received from webservice
                $response = $this->responseDataMapper->createTrackResponse(
                    (string) $shipmentNumber,
                    $cancelRequest->getSalesShipment(),
                    $cancelRequest->getSalesTrack()
                );

                $this->trackResponses[$shipmentNumber] = $response;
            } else {
                // negative response received from webservice, details available in api log
                $response = $this->responseDataMapper->createErrorResponse(
                    (string) $shipmentNumber,
                    __('Shipment %1 could not be cancelled.', $shipmentNumber),
                    $cancelRequest->getSalesShipment(),
                    $cancelRequest->getSalesTrack()
                );

                $this->errorResponses[$shipmentNumber] = $response;
            }
        }

        return $this;
    }

    /**
     * @return TrackResponseInterface[]
     */
    public function getTracks()
    {
        return $this->trackResponses;
    }

    /**
     * @return TrackErrorResponseInterface[]
     */
    public function getErrors()
    {
        return $this->errorResponses;
    }
}
