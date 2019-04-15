<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model;

use Dhl\Paket\Model\Cancel\Request as CancelRequest;
use Dhl\Paket\Model\Cancel\RequestFactory as CancelRequestFactory;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Webservice\ApiGateway;
use Dhl\Paket\Webservice\ApiGatewayFactory;
use Dhl\ShippingCore\Api\LabelStatusManagementInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\BulkException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order\Shipment\TrackRepository;
use Magento\Sales\Model\ResourceModel\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection;
use Psr\Log\LoggerInterface;

/**
 * Class ShipmentManagement
 *
 * @package Dhl\Paket\Model
 */
class ShipmentManagement
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var TrackRepository
     */
    private $trackRepository;

    /**
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    /**
     * @var ApiGateway[]
     */
    private $apiGateways;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Shipment
     */
    private $shipmentResource;

    /**
     * @var CancelRequestFactory
     */
    private $cancelRequestFactory;

    /**
     * ShipmentManagement constructor.
     *
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param TrackRepository $trackRepository
     * @param ApiGatewayFactory $apiGatewayFactory
     * @param ApiGateway[] $apiGateways
     * @param LoggerInterface $logger
     * @param Shipment $shipmentResource
     * @param CancelRequestFactory $cancelRequestFactory
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        TrackRepository $trackRepository,
        ApiGatewayFactory $apiGatewayFactory,
        array $apiGateways,
        LoggerInterface $logger,
        LabelStatusManagementInterface $labelStatusManagement,
        Shipment $shipmentResource,
        CancelRequestFactory $cancelRequestFactory
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->trackRepository = $trackRepository;
        $this->apiGatewayFactory = $apiGatewayFactory;
        $this->apiGateways = $apiGateways;
        $this->logger = $logger;
        $this->shipmentResource = $shipmentResource;
        $this->cancelRequestFactory = $cancelRequestFactory;
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
     * @param ShipmentInterface[] $shipments
     * @throws CouldNotDeleteException
     */
    public function cancelShipments(array $shipments)
    {
        if (empty($shipments)) {
            return;
        }

        $bulkException = new BulkException();

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        foreach ($shipments as $shipment) {
            $searchCriteria = $this->buildShipmentSearchCriteria($shipment->getId());

            /** @var Collection $trackCollection */
            $trackCollection = $this->trackRepository->getList($searchCriteria);
            $trackNumbers = $trackCollection->getColumnValues(ShipmentTrackInterface::TRACK_NUMBER);

            $cancelRequests = $this->createCancelRequests($shipment, $trackNumbers);

            // cancel shipment orders at the api
            $api = $this->getApiGateway((int) $shipment->getStoreId());
            $apiResult = $api->cancelShipments($cancelRequests);

            $unCancelled = array_diff($trackNumbers, $apiResult);
            if (!empty($unCancelled)) {
                $bulkException->addError(__('Shipment orders %1 could not be cancelled.', implode(', ', $unCancelled)));
            }

            try {
                // delete tracks and unset shipping label
                $this->shipmentResource->beginTransaction();
                foreach ($trackCollection as $track) {
                    if (\in_array($track->getNumber(), $apiResult, true)) {
                        $this->trackRepository->delete($track);
                    }
                }
                if (empty($unCancelled)) {
                    $shipment->setShippingLabel(null);
                    $this->shipmentResource->save($shipment);
                }
                $this->shipmentResource->commit();
            } catch (LocalizedException $exception) {
                $bulkException->addException($exception);
                $this->shipmentResource->rollBack();
            } catch (\Exception $exception) {
                $bulkException->addError(__('Unable to delete tracks or shipping label: %1', $exception->getMessage()));
                $this->shipmentResource->rollBack();
            }
        }

        if ($bulkException->wasErrorAdded()) {
            throw new CouldNotDeleteException(
                __('An error occurred during shipment order cancellation.'),
                $bulkException
            );
        }
    }

    /**
     * Create search criteria from shipmentId and carrierCode filter
     *
     * @param string $shipmentId
     * @return SearchCriteria
     *
     */
    private function buildShipmentSearchCriteria($shipmentId): SearchCriteria
    {
        $this->filterBuilder->setField(ShipmentTrackInterface::PARENT_ID);
        $this->filterBuilder->setConditionType('eq');
        $this->filterBuilder->setValue($shipmentId);
        $shipmentIdFilter = $this->filterBuilder->create();

        $this->filterBuilder->setField(ShipmentTrackInterface::CARRIER_CODE);
        $this->filterBuilder->setConditionType('eq');
        $this->filterBuilder->setValue(Paket::CARRIER_CODE);
        $carrierCodeFilter = $this->filterBuilder->create();

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter($shipmentIdFilter);
        $searchCriteriaBuilder->addFilter($carrierCodeFilter);

        return $searchCriteriaBuilder->create();
    }

    /**
     * Create cancellation requests from track numbers and shipment
     *
     * @param ShipmentInterface $shipment
     * @param array $trackNumbers
     * @return CancelRequest[]
     */
    private function createCancelRequests(ShipmentInterface $shipment, array $trackNumbers): array
    {
        /** @var CancelRequest[] $cancelRequests */
        return array_map(
            function ($trackNumber) use ($shipment) {
                return $this->cancelRequestFactory->create(
                    [
                        'data' => [
                            CancelRequest::TRACK_ID => $trackNumber,
                            CancelRequest::SHIPMENT => $shipment,
                            CancelRequest::ORDER => $shipment->getOrder(),
                        ],
                    ]
                );
            },
            $trackNumbers
        );
    }
}
