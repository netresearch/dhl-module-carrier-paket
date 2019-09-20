<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model;

use Dhl\Paket\Webservice\ApiGateway;
use Dhl\Paket\Webservice\ApiGatewayFactory;
use Dhl\ShippingCore\Api\BulkShipment\BulkLabelCancellationInterface;
use Dhl\ShippingCore\Api\BulkShipment\BulkLabelCreationInterface;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\ShipmentResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;
use Dhl\ShippingCore\Api\Pipeline\TrackResponseProcessorInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class ShipmentManagement
 *
 * Central entrypoint for creating and deleting shipments.
 *
 * @package Dhl\Paket\Model
 */
class ShipmentManagement implements BulkLabelCreationInterface, BulkLabelCancellationInterface
{
    /**
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    /**
     * @var ShipmentResponseProcessorInterface
     */
    private $createResponseProcessor;

    /**
     * @var TrackResponseProcessorInterface
     */
    private $deleteResponseProcessor;

    /**
     * @var ApiGateway[]
     */
    private $apiGateways;

    /**
     * ShipmentManagement constructor.
     *
     * @param ApiGatewayFactory $apiGatewayFactory
     * @param ShipmentResponseProcessorInterface $createResponseProcessor
     * @param TrackResponseProcessorInterface $deleteResponseProcessor
     */
    public function __construct(
        ApiGatewayFactory $apiGatewayFactory,
        ShipmentResponseProcessorInterface $createResponseProcessor,
        TrackResponseProcessorInterface $deleteResponseProcessor
    ) {
        $this->apiGatewayFactory = $apiGatewayFactory;
        $this->createResponseProcessor = $createResponseProcessor;
        $this->deleteResponseProcessor = $deleteResponseProcessor;
    }

    /**
     * Create api gateway.
     *
     * API gateways are created with store specific configuration and configured post-processors (bulk or popup).
     *
     * @param int $storeId
     * @return ApiGateway
     */
    private function getApiGateway(int $storeId): ApiGateway
    {
        if (!isset($this->apiGateways[$storeId])) {
            $api = $this->apiGatewayFactory->create(
                [
                    'storeId' => $storeId,
                    'createResponseProcessor' => $this->createResponseProcessor,
                    'deleteResponseProcessor' => $this->deleteResponseProcessor,
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
     * Cancellation requests are divided by store for multi-store support (different DHL account configurations).
     *
     * @param TrackRequestInterface[] $cancelRequests
     * @return TrackResponseInterface[]
     */
    public function cancelLabels(array $cancelRequests): array
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
