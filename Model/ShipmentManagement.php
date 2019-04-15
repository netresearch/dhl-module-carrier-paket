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
use Magento\Framework\Api\Filter;
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
            $searchCriteria = $this->buildShipmentSearchCriteria($shipment);

            /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $searchResult */
            $searchResult = $this->trackRepository->getList($searchCriteria);
            $trackNumbers = $searchResult->getColumnValues(ShipmentTrackInterface::TRACK_NUMBER);

            $cancelRequests = $this->createCancelRequests($shipment, $trackNumbers);

            // cancel shipment orders at the api
            $api = $this->getApiGateway((int) $shipment->getStoreId());
            $apiResult = $api->cancelShipments($cancelRequests);

            $diff = array_diff($trackNumbers, $apiResult);
            if (!empty($diff)) {
                $bulkException->addError(__('Shipment orders %1 could not be cancelled.', implode(', ', $diff)));
                continue;
            }

            // delete tracks and unset shipping label
            $this->shipmentResource->beginTransaction();

            try {
                foreach ($searchResult as $track) {
                    if (in_array($track->getNumber(), $apiResult, true)) {
                        $this->trackRepository->delete($track);
                    }
                }

                $shipment->setShippingLabel(null);
                $this->shipmentResource->save($shipment);
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
     * Create filter for shipmentId
     *
     * @param ShipmentInterface $shipment
     * @return Filter
     */
    private function createShipmentIdFilter(ShipmentInterface $shipment): Filter
    {
        $this->filterBuilder->setField(ShipmentTrackInterface::PARENT_ID);
        $this->filterBuilder->setConditionType('eq');
        $this->filterBuilder->setValue($shipment->getEntityId());

        return $this->filterBuilder->create();
    }

    /**
     * Create filter for carrier code
     *
     * @return Filter
     */
    private function createCarrierCodeFilter(): Filter
    {
        $this->filterBuilder->setField(ShipmentTrackInterface::CARRIER_CODE);
        $this->filterBuilder->setConditionType('eq');
        $this->filterBuilder->setValue(Paket::CARRIER_CODE);

        return $this->filterBuilder->create();
    }

    /**
     * Create search criteria from shipmentId and carrierCode filter
     *
     * @param $shipment
     * @return SearchCriteria
     * @see createCarrierCodeFilter
     * @see createShipmentIdFilter
     *
     */
    private function buildShipmentSearchCriteria($shipment): SearchCriteria
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter($this->createShipmentIdFilter($shipment));
        $searchCriteriaBuilder->addFilter($this->createCarrierCodeFilter());

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
