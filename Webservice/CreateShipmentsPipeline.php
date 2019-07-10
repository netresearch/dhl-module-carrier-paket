<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Webservice\Shipment\RequestDataMapper;
use Dhl\Paket\Webservice\Shipment\ResponseDataMapper;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\ShipmentErrorResponseInterface;
use Dhl\ShippingCore\Api\RequestValidatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class CreateShipmentsPipeline
 *
 * @package Dhl\Paket\Webservice
 */
class CreateShipmentsPipeline
{
    /**
     * @var RequestValidatorInterface
     */
    private $requestValidator;

    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    /**
     * @var ShipmentServiceInterface
     */
    private $shipmentService;

    /**
     * Core shipment requests.
     *
     * @var Request[]
     */
    private $shipmentRequests = [];

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
     * Error messages occurred during pipeline execution.
     *
     * @var string[][]|\Magento\Sales\Api\Data\ShipmentInterface[][]
     */
    private $errors = [];

    /**
     * Label response suitable for processing by the core.
     *
     * @var LabelResponseInterface[]
     */
    private $shipmentResponses = [];

    /**
     * Error response suitable for processing by the core. Contains request id / sequence number.
     *
     * @var ShipmentErrorResponseInterface[]
     */
    private $errorResponses = [];

    /**
     * Pipeline constructor.
     *
     * @param RequestValidatorInterface $requestValidator
     * @param RequestDataMapper $requestDataMapper
     * @param ResponseDataMapper $responseDataMapper
     * @param ShipmentServiceInterface $shipmentService
     * @param Request[] $shipmentRequests
     */
    public function __construct(
        RequestValidatorInterface $requestValidator,
        RequestDataMapper $requestDataMapper,
        ResponseDataMapper $responseDataMapper,
        ShipmentServiceInterface $shipmentService,
        array $shipmentRequests
    ) {
        $this->requestValidator = $requestValidator;
        $this->requestDataMapper = $requestDataMapper;
        $this->responseDataMapper = $responseDataMapper;
        $this->shipmentService = $shipmentService;
        $this->shipmentRequests = $shipmentRequests;
    }

    /**
     * Validate shipment requests.
     *
     * Invalid requests are removed from shipment requests and instantly added as label failures.
     *
     * @return $this
     */
    public function validate()
    {
        $callback = function (Request $request, int $requestIndex) {
            try {
                $this->requestValidator->validate($request);

                return true;
            } catch (ValidatorException $exception) {
                $this->errors[$requestIndex] = [
                    'shipment' => $request->getOrderShipment(),
                    'message' => $exception->getMessage(),
                ];

                return false;
            }
        };

        // keep only the shipment requests that validate
        $this->shipmentRequests = array_filter($this->shipmentRequests, $callback, ARRAY_FILTER_USE_BOTH);

        return $this;
    }

    /**
     * Transform core shipment requests into request objects suitable for the label API.
     *
     * Requests with mapping errors are removed from requests and instantly added as error responses.
     *
     * @return $this
     */
    public function map()
    {
        $callback = function (Request $request, int $requestIndex) {
            try {
                $shipmentOrder = $this->requestDataMapper->mapRequest((string) $requestIndex, $request);
                $this->apiRequests[$requestIndex] = $shipmentOrder;

                return true;
            } catch (LocalizedException $exception) {
                $this->errors[$requestIndex] = [
                    'shipment' => $request->getOrderShipment(),
                    'message' => $exception->getMessage(),
                ];

                return false;
            }
        };

        // keep only the shipment requests that could be mapped
        $this->shipmentRequests = array_filter($this->shipmentRequests, $callback, ARRAY_FILTER_USE_BOTH);

        return $this;
    }

    /**
     * Send label request objects to shipment service.
     *
     * @return $this
     */
    public function send()
    {
        if (empty($this->apiRequests)) {
            return $this;
        }

        try {
            $shipments = $this->shipmentService->createShipments($this->apiRequests);
            // add request id as response index
            foreach ($shipments as $shipment) {
                $this->apiResponses[$shipment->getSequenceNumber()] = $shipment;
            }
        } catch (ServiceException $exception) {
            // mark all requests as failed
            foreach ($this->shipmentRequests as $requestIndex => $shipmentRequest) {
                $this->errors[$requestIndex] = [
                    'shipment' => $shipmentRequest->getOrderShipment(),
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $this;
    }

    /**
     * Transform collected results into response objects suitable for processing by the core.
     *
     * The `sequence_number` property is set to the shipment request packages during request mapping.
     *
     * @return $this
     * @see \Dhl\Paket\Webservice\Shipment\RequestDataMapper::mapRequest
     *
     */
    public function mapResponse()
    {
        foreach ($this->errors as $requestIndex => $details) {
            // no response received from webservice for particular shipment request
            $response = $this->responseDataMapper->createErrorResponse(
                (string) $requestIndex,
                __('Label could not be created: %1.', $details['message']),
                $details['shipment']
            );
            $this->errorResponses[$requestIndex] = $response;
        }

        foreach ($this->shipmentRequests as $requestIndex => $shipmentRequest) {
            if (isset($this->errors[$requestIndex])) {
                // detailed error message was already processed
                continue;
            }

            /** @var Shipment $shipment */
            $shipment = $shipmentRequest->getOrderShipment();
            $orderIncrementId = $shipment->getOrder()->getIncrementId();

            foreach ($shipmentRequest->getData('packages') as $packageId => $package) {
                $requestedPackageId = $shipmentRequest->getData('package_id');
                if (!empty($requestedPackageId) && ($requestedPackageId !== $packageId)) {
                    // package was not sent, skip.
                    continue;
                }

                // for paket requests, this is just a consecutive number
                $sequenceNumber = $package['sequence_number'];
                if (isset($this->apiResponses[$sequenceNumber])) {
                    // positive response received from webservice
                    $response = $this->responseDataMapper->createShipmentResponse(
                        $this->apiResponses[$sequenceNumber],
                        $shipmentRequest->getOrderShipment()
                    );

                    $this->shipmentResponses[$sequenceNumber] = $response;
                } else {
                    // negative response received from webservice, details available in api log
                    $response = $this->responseDataMapper->createErrorResponse(
                        (string) $requestIndex,
                        __('Label for order %1, package %2 could not be created.', $orderIncrementId, $packageId),
                        $shipmentRequest->getOrderShipment()
                    );

                    $this->errorResponses[$requestIndex] = $response;
                }
            }
        }

        return $this;
    }

    /**
     * @return LabelResponseInterface[]
     */
    public function getLabels()
    {
        return $this->shipmentResponses;
    }

    /**
     * @return ShipmentErrorResponseInterface[]
     */
    public function getErrors()
    {
        return $this->errorResponses;
    }
}
