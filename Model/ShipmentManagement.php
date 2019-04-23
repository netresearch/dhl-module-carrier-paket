<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model;

use Dhl\Paket\Model\Shipment\CancelRequest;
use Dhl\Paket\Webservice\ApiGateway;
use Dhl\Paket\Webservice\ApiGatewayFactory;
use Magento\Framework\Exception\BulkException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order\Shipment\TrackRepository;
use Magento\Sales\Model\ResourceModel\Order\Shipment;
use Psr\Log\LoggerInterface;

/**
 * Class ShipmentManagement
 *
 * @package Dhl\Paket\Model
 */
class ShipmentManagement
{
    /**
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    /**
     * @var TrackRepository
     */
    private $trackRepository;

    /**
     * @var Shipment
     */
    private $shipmentResource;

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
     * @param ApiGatewayFactory $apiGatewayFactory
     * @param TrackRepository $trackRepository
     * @param Shipment $shipmentResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiGatewayFactory $apiGatewayFactory,
        TrackRepository $trackRepository,
        Shipment $shipmentResource,
        LoggerInterface $logger
    ) {
        $this->apiGatewayFactory = $apiGatewayFactory;
        $this->trackRepository = $trackRepository;
        $this->shipmentResource = $shipmentResource;
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
            $api = $this->apiGatewayFactory->create(
                [
                    'logger' => $this->logger,
                    'storeId' => $storeId,
                ]
            );

            $this->apiGateways[$storeId] = $api;
        }

        return $this->apiGateways[$storeId];
    }

    /**
     * Cancel shipment orders at the DHL Paket API alongside associated tracks and shipping labels.
     *
     * @param CancelRequest[] $cancelRequests
     * @throws CouldNotDeleteException
     */
    public function cancelLabels(array $cancelRequests)
    {
        if (empty($cancelRequests)) {
            return;
        }

        $bulkException = new BulkException();
        $apiRequests = [];

        // divide cancel requests by store as they may use different api configurations
        foreach ($cancelRequests as $cancelRequest) {
            $storeId = (int) $cancelRequest->getShipment()->getStoreId();
            $apiRequests[$storeId][] = $cancelRequest;
        }

        /**
         * @var int $storeId
         * @var CancelRequest[] $storeApiRequests
         */
        foreach ($apiRequests as $storeId => $storeApiRequests) {
            // send all cancel requests of one store to the api
            $api = $this->getApiGateway($storeId);
            $apiResult = $api->cancelShipments($storeApiRequests);

            // process response
            $cancelledTracks = [];
            $failedShipments = [];

            foreach ($storeApiRequests as $storeApiRequest) {
                $trackNumber = $storeApiRequest->getTrack()->getTrackNumber();
                $shipmentId = $storeApiRequest->getShipment()->getEntityId();
                if (!in_array($trackNumber, $apiResult, true)) {
                    // shipment was not cancelled at the api, mark overall shipment failed and add error.
                    $failedShipments[$shipmentId] = $shipmentId;
                    $bulkException->addError(__('Shipment order %1 could not be cancelled.', $trackNumber));
                } else {
                    $cancelledTracks[$shipmentId][] = $storeApiRequest->getTrack();
                }
            }

            /**
             * @var int $shipmentId
             * @var CancelRequest[] $shipmentTracks
             */
            foreach ($cancelledTracks as $shipmentId => $shipmentTracks) {
                try {
                    $this->shipmentResource->beginTransaction();

                    // delete cancelled tracks of a shipment
                    array_walk(
                        $shipmentTracks,
                        function (ShipmentTrackInterface $track) {
                            $this->trackRepository->delete($track);
                        }
                    );

                    // unset combined label if all tracks (=labels) were cancelled at the api
                    if (!array_key_exists($shipmentId, $failedShipments)) {
                        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                        $shipment = $shipmentTracks[0]->getShipment();
                        $shipment->setShippingLabel(null);
                        $this->shipmentResource->save($shipment);
                    }

                    $this->shipmentResource->commit();
                } catch (LocalizedException $exception) {
                    $bulkException->addException($exception);
                    $this->shipmentResource->rollBack();
                } catch (\Exception $exception) {
                    $bulkException->addError(
                        __('Unable to delete tracks or shipping label: %1', $exception->getMessage())
                    );
                    $this->shipmentResource->rollBack();
                }
            }
        }

        if ($bulkException->wasErrorAdded()) {
            throw new CouldNotDeleteException(
                __('An error occurred during shipment order cancellation.'),
                $bulkException
            );
        }
    }
}
