<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Model\Shipment\CancelRequest;
use Dhl\Paket\Webservice\CarrierResponse\ErrorResponse;
use Dhl\Paket\Webservice\CarrierResponse\FailureResponse;
use Dhl\Paket\Webservice\CarrierResponse\ShipmentResponse;
use Dhl\Paket\Webservice\Processor\OperationProcessorInterface;
use Dhl\Paket\Webservice\Shipment\RequestDataMapper;
use Dhl\Paket\Webservice\Shipment\ResponseDataMapper;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Psr\Log\LoggerInterface;

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
     * @var ShipmentService
     */
    private $shipmentService;

    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    /**
     * @var OperationProcessorInterface
     */
    private $operationProcessor;

    /**
     * ApiGateway constructor.
     *
     * @param ShipmentServiceFactory $serviceFactory
     * @param RequestDataMapper $requestDataMapper
     * @param ResponseDataMapper $responseDataMapper
     * @param OperationProcessorInterface $operationProcessor
     * @param LoggerInterface $logger
     * @param int $storeId
     */
    public function __construct(
        ShipmentServiceFactory $serviceFactory,
        RequestDataMapper $requestDataMapper,
        ResponseDataMapper $responseDataMapper,
        OperationProcessorInterface $operationProcessor,
        LoggerInterface $logger,
        int $storeId = 0
    ) {
        $this->requestDataMapper = $requestDataMapper;
        $this->responseDataMapper = $responseDataMapper;
        $this->operationProcessor = $operationProcessor;
        $this->shipmentService = $serviceFactory->create(
            [
                'logger' => $logger,
                'storeId' => $storeId,
            ]
        );
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
     * @param \Magento\Shipping\Model\Shipment\Request[] $shipmentRequests
     * @return ShipmentResponse[]|ErrorResponse[]|FailureResponse[]
     */
    public function createShipments(array $shipmentRequests): array
    {
        // prohibit return shipment requests
        $returnRequests = array_filter(
            $shipmentRequests,
            function (DataObject $request) {
                return ($request->getData('is_return') || $request instanceof ReturnShipment);
            }
        );

        if (!empty($returnRequests)) {
            $message = __('Return shipments are not supported.');
            $response = [$this->responseDataMapper->createFailureResponse($message)];

            return $response;
        }

        // map shipment requests to shipment orders
        $shipmentOrders = [];
        foreach ($shipmentRequests as $sequenceNumber => $shipmentRequest) {
            $sequenceNumber = (string) $sequenceNumber;
            $shipmentOrders[$sequenceNumber] = $this->requestDataMapper->mapRequest($sequenceNumber, $shipmentRequest);
        }

        return $this->handleRequestAndResponse($shipmentRequests, $shipmentOrders);
    }

    /**
     * @param \Magento\Shipping\Model\Shipment\Request[] $shipmentRequests
     * @param array $shipmentOrders
     *
     * @return ShipmentResponse[]|ErrorResponse[]|FailureResponse[]
     */
    public function handleRequestAndResponse(array $shipmentRequests, array $shipmentOrders): array
    {
        try {
            $createdShipments = [];
            $response = [];

            // send shipment orders to web service
            $shipments = $this->shipmentService->createShipments($shipmentOrders);

            // attach sequence number to shipments received from web service
            foreach ($shipments as $shipment) {
                $createdShipments[$shipment->getSequenceNumber()] = $shipment;
            }

            // divide shipment orders into successful label creations and failures.
            foreach ($shipmentOrders as $sequenceNumber => $shipmentOrder) {
                $sequenceNumber = (string) $sequenceNumber;
                if (isset($createdShipments[$sequenceNumber])) {
                    $response[] = $this->responseDataMapper->createShipmentResponse(
                        $sequenceNumber,
                        $createdShipments[$sequenceNumber]
                    );
                } else {
                    $response[] = $this->responseDataMapper->createErrorResponse(
                        $sequenceNumber,
                        __('Label for shipment request %1 could not be created.', $sequenceNumber)
                    );
                }
            }
        } catch (ServiceException $exception) {
            $message = __('Requested shipments could not be created: %1', $exception->getMessage());
            $response = [$this->responseDataMapper->createFailureResponse($message)];
        } finally {
            // post-process response, i.e. set new label status to order
            $this->operationProcessor->processCreateShipmentsResponse($shipmentRequests, $response);

            return $response;
        }
    }

    /**
     * Send cancellation request to API, inform label status management, return result.
     *
     * @param CancelRequest[] $cancelRequests
     * @return string[]
     */
    public function cancelShipments(array $cancelRequests): array
    {
        try {
            $shipmentNumbers = array_map(function (CancelRequest $cancelRequest) {
                return $cancelRequest->getTrack()->getTrackNumber();
            }, $cancelRequests);

            $cancelledShipments = $this->shipmentService->cancelShipments($shipmentNumbers);

            $this->operationProcessor->processCancelShipmentsResponse($cancelRequests, $cancelledShipments);

            return $cancelledShipments;
        } catch (ServiceException $exception) {
            return [];
        }
    }
}
