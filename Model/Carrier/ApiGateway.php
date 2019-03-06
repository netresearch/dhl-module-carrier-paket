<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Webservice\Shipment\RequestDataMapper;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Psr\Log\LoggerInterface;

/**
 * Class ApiGateway
 *
 * Magento carrier-aware wrapper around the DHL Paket API SDK.
 *
 * @package Dhl\Paket\Model
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ApiGateway
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $storeId;

    /**
     * ApiGateway constructor.
     * @param ModuleConfig $moduleConfig
     * @param RequestDataMapper $requestDataMapper
     * @param DataObjectFactory $dataObjectFactory
     * @param LoggerInterface $logger
     * @param int $storeId
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        RequestDataMapper $requestDataMapper,
        DataObjectFactory $dataObjectFactory,
        LoggerInterface $logger,
        int $storeId = 0
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->requestDataMapper = $requestDataMapper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->logger = $logger;
        $this->storeId = $storeId;
    }

    /**
     * Create the SDK service.
     *
     * @return \Dhl\Sdk\Paket\Bcs\Service\ShipmentService
     */
    private function getShipmentService(): \Dhl\Sdk\Paket\Bcs\Service\ShipmentService
    {
        $authStorage = new \Dhl\Sdk\Paket\Bcs\Auth\AuthenticationStorage(
            $this->moduleConfig->getAuthUsername($this->storeId),
            $this->moduleConfig->getAuthPassword($this->storeId),
            $this->moduleConfig->getUser($this->storeId),
            $this->moduleConfig->getSignature($this->storeId)
        );
        $serviceFactory = new \Dhl\Sdk\Paket\Bcs\Service\ServiceFactory();
        $service = $serviceFactory->createShipmentService(
            $authStorage,
            $this->logger,
            $this->moduleConfig->isSandboxMode($this->storeId)
        );

        return $service;
    }

    /**
     * Map errors into error object.
     *
     * @param string[] $messages
     * @return DataObject[]
     */
    private function createErrorResponse(array $messages): array
    {
        $message = implode(' ', $messages);
        $response = $this->dataObjectFactory->create(['data' => ['errors' => $message]]);
        return [$response];
    }

    /**
     * Map created shipments into data objects.
     *
     * @param \Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface[] $shipments
     * @return DataObject[]
     */
    private function createShipmentResponse(array $shipments): array
    {
        $response = array_map(function (\Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface $shipment) {
            $responseData = [
                'tracking_number' => $shipment->getShipmentNumber(),
                'shipping_label_content' => $shipment->getLabels(),
            ];

            return $this->dataObjectFactory->create(['data' => $responseData]);
        }, $shipments);

        return $response;
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
        $returnRequests = array_filter($shipmentRequests, function (DataObject $request) {
            return ($request->getData('is_return') || $request instanceof ReturnShipment);
        });

        if (!empty($returnRequests)) {
            $message = __('Return shipments are not supported.');
            $response = $this->createErrorResponse([$message]);
            return $response;
        }

        $service = $this->getShipmentService();
        $shipmentOrders = array_map(function (Request $shipmentRequest) {
            return $this->requestDataMapper->mapRequest($shipmentRequest);
        }, $shipmentRequests);

        try {
            $shipments = $service->createShipments($shipmentOrders);
            $response = $this->createShipmentResponse($shipments);

            return $response;
        } catch (\Dhl\Sdk\Paket\Bcs\Exception\ServiceException $exception) {
            $message = __('Requested shipments could not be created: %s', $exception->getMessage());
            $response = $this->createErrorResponse([$message]);

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
        $service = $this->getShipmentService();
        try {
            $cancelled = $service->cancelShipments($shipmentNumbers);
            return $cancelled;
        } catch (\Dhl\Sdk\Paket\Bcs\Exception\ServiceException $exception) {
            return [];
        }
    }
}
