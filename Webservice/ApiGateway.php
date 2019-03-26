<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Webservice\Shipment\RequestDataMapper;
use Dhl\Paket\Webservice\Shipment\ResponseDataMapper;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Shipment\Request;
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
     * ApiGateway constructor.
     *
     * @param ShipmentServiceFactory $serviceFactory
     * @param RequestDataMapper $requestDataMapper
     * @param ResponseDataMapper $responseDataMapper
     * @param LoggerInterface $logger
     * @param int $storeId
     */
    public function __construct(
        ShipmentServiceFactory $serviceFactory,
        RequestDataMapper $requestDataMapper,
        ResponseDataMapper $responseDataMapper,
        LoggerInterface $logger,
        int $storeId = 0
    ) {
        $this->requestDataMapper = $requestDataMapper;
        $this->responseDataMapper = $responseDataMapper;
        $this->shipmentService = $serviceFactory->create(
            [
                'logger' => $logger,
                'storeId' => $storeId,
            ]
        );
    }

    /**
     * Convert shipment requests to shipment orders, send to API, return result.
     *
     * The mapped result can be
     * - an array of tracking-label pairs or
     * - an array of errors.
     *
     * Note that the SDK does not return errors per shipment, only accumulated into one exception message.
     *
     * @param \Magento\Shipping\Model\Shipment\Request[] $shipmentRequests
     * @return DataObject[]
     */
    public function createShipments(array $shipmentRequests): array
    {
        $returnRequests = array_filter(
            $shipmentRequests,
            function (DataObject $request) {
                return ($request->getData('is_return') || $request instanceof ReturnShipment);
            }
        );

        if (!empty($returnRequests)) {
            $message = __('Return shipments are not supported.');
            $response = $this->responseDataMapper->createErrorResponse([$message]);

            return $response;
        }

        $shipmentOrders = array_map(
            function (Request $shipmentRequest) {
                return $this->requestDataMapper->mapRequest($shipmentRequest);
            },
            $shipmentRequests
        );

        try {
            $shipments = $this->shipmentService->createShipments($shipmentOrders);
            $response = $this->responseDataMapper->createShipmentsResponse($shipments);

            return $response;
        } catch (ServiceException $exception) {
            $message = __('Requested shipments could not be created: %1', $exception->getMessage());
            $response = $this->responseDataMapper->createErrorResponse([$message]);

            return $response;
        }
    }

    /**
     * Send cancellation request to API, return result.
     *
     * @param string[] $shipmentNumbers
     * @return string[]
     */
    public function cancelShipments(array $shipmentNumbers): array
    {
        try {
            return $this->shipmentService->cancelShipments($shipmentNumbers);
        } catch (ServiceException $exception) {
            return [];
        }
    }
}
