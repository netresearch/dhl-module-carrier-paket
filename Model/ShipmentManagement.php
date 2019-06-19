<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model;

use Dhl\Paket\Webservice\ApiGateway;
use Dhl\Paket\Webservice\ApiGatewayFactory;
use Dhl\Paket\Webservice\ShipmentServiceFactory;
use Dhl\ShippingCore\Api\BulkLabelCreationInterface;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\ShipmentResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Api\ShipmentResponseProcessorInterface;
use Dhl\ShippingCore\Api\TrackResponseProcessorInterface;
use Magento\Shipping\Model\Shipment\Request;
use Psr\Log\LoggerInterface;

/**
 * Class ShipmentManagement
 *
 * Central entrypoint for creating and deleting shipments.
 *
 * @package Dhl\Paket\Model
 */
class ShipmentManagement implements BulkLabelCreationInterface
{
    /**
     * @var ShipmentServiceFactory
     */
    private $shipmentServiceFactory;

    /**
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    /**
     * @var ShipmentResponseProcessorInterface
     */
    private $creationProcessor;

    /**
     * @var TrackResponseProcessorInterface
     */
    private $deletionProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ApiGateway[]
     */
    private $apiGateways;

    /**
     * ShipmentManagement constructor.
     *
     * @param ShipmentServiceFactory $shipmentServiceFactory
     * @param ApiGatewayFactory $apiGatewayFactory
     * @param ShipmentResponseProcessorInterface $creationProcessor
     * @param TrackResponseProcessorInterface $deletionProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShipmentServiceFactory $shipmentServiceFactory,
        ApiGatewayFactory $apiGatewayFactory,
        ShipmentResponseProcessorInterface $creationProcessor,
        TrackResponseProcessorInterface $deletionProcessor,
        LoggerInterface $logger
    ) {
        $this->shipmentServiceFactory = $shipmentServiceFactory;
        $this->apiGatewayFactory = $apiGatewayFactory;
        $this->creationProcessor = $creationProcessor;
        $this->deletionProcessor = $deletionProcessor;
        $this->logger = $logger;
    }

    /**
     * Create api gateway with store specific configuration.
     *
     * @param int $storeId
     * @return ApiGateway
     */
    private function getApiGateway(int $storeId): ApiGateway
    {
        if (!isset($this->apiGateways[$storeId])) {
            $shipmentService = $this->shipmentServiceFactory->create(
                [
                    'logger' => $this->logger,
                    'storeId' => $storeId,
                ]
            );

            $api = $this->apiGatewayFactory->create(
                [
                    'shipmentService' => $shipmentService,
                    'creationProcessor' => $this->creationProcessor,
                    'deletionProcessor' => $this->deletionProcessor,
                ]
            );

            $this->apiGateways[$storeId] = $api;
        }

        return $this->apiGateways[$storeId];
    }

    /**
     * Create shipment labels at DHL Paket API
     *
     * Shipment requests are divided by store for multi-store support (different DHL account configurations).
     *
     * @param Request[] $shipmentRequests
     * @return ShipmentResponseInterface[]
     */
    public function createLabels(array $shipmentRequests): array
    {
        if (empty($shipmentRequests)) {
            return [];
        }

        $apiRequests = [];
        $apiResults = [];

        foreach ($shipmentRequests as $shipmentRequest) {
            $storeId = (int) $shipmentRequest->getOrderShipment()->getStoreId();
            $apiRequests[$storeId][] = $shipmentRequest;
        }

        foreach ($apiRequests as $storeId => $storeApiRequests) {
            $api = $this->getApiGateway($storeId);
            $apiResults[$storeId] = $api->createShipments($storeApiRequests);
        }

        if (!empty($apiResults)) {
            // convert results per store to flat response
            $apiResults = array_reduce($apiResults, 'array_merge', []);
        }

        return $apiResults;
    }

    /**
     * Cancel shipment orders at the DHL Paket API alongside associated tracks and shipping labels.
     *
     * @param TrackRequestInterface[] $cancelRequests
     * @return TrackResponseInterface[]
     */
    public function cancelLabels(array $cancelRequests)
    {
        if (empty($cancelRequests)) {
            return [];
        }

        $apiRequests = [];
        $apiResults = [];

        // divide cancel requests by store as they may use different api configurations
        foreach ($cancelRequests as $shipmentNumber => $cancelRequest) {
            $storeId = $cancelRequest->getStoreId();
            $apiRequests[$storeId][$shipmentNumber] = $cancelRequest;
        }

        foreach ($apiRequests as $storeId => $storeApiRequests) {
            $api = $this->getApiGateway($storeId);
            $apiResults[$storeId] = $api->cancelShipments($storeApiRequests);
        }

        if (!empty($apiResults)) {
            // convert results per store to flat response
            $apiResults = array_reduce($apiResults, 'array_merge', []);
        }

        return $apiResults;
    }
}
