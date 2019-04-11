<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Webservice\ApiGateway;
use Dhl\Paket\Webservice\ApiGatewayFactory;
use Dhl\ShippingCore\Api\LabelStatusManagementInterface;
use Magento\Framework\Api\FilterBuilder;
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
     * @var LabelStatusManagementInterface
     */
    private $labelStatusManagement;

    /**
     * @var Shipment
     */
    private $shipmentResource;

    /**
     * ShipmentManagement constructor.
     *
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param TrackRepository $trackRepository
     * @param ApiGatewayFactory $apiGatewayFactory
     * @param LoggerInterface $logger
     * @param LabelStatusManagementInterface $labelStatusManagement
     * @param Shipment $shipmentResource
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        TrackRepository $trackRepository,
        ApiGatewayFactory $apiGatewayFactory,
        LoggerInterface $logger,
        LabelStatusManagementInterface $labelStatusManagement,
        Shipment $shipmentResource
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->trackRepository = $trackRepository;
        $this->apiGatewayFactory = $apiGatewayFactory;
        $this->logger = $logger;
        $this->labelStatusManagement = $labelStatusManagement;
        $this->shipmentResource = $shipmentResource;
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
            $api = $this->apiGatewayFactory->create([
                'logger' => $this->logger,
                'storeId' => $storeId
            ]);

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
            $this->filterBuilder->setField(ShipmentTrackInterface::PARENT_ID);
            $this->filterBuilder->setConditionType('eq');
            $this->filterBuilder->setValue($shipment->getEntityId());
            $shipmentIdFilter = $this->filterBuilder->create();

            $this->filterBuilder->setField(ShipmentTrackInterface::CARRIER_CODE);
            $this->filterBuilder->setConditionType('eq');
            $this->filterBuilder->setValue(Paket::CARRIER_CODE);
            $carrierFilter = $this->filterBuilder->create();

            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder->addFilter($shipmentIdFilter);
            $searchCriteriaBuilder->addFilter($carrierFilter);
            $searchCriteria = $searchCriteriaBuilder->create();

            /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $searchResult */
            $searchResult = $this->trackRepository->getList($searchCriteria);
            $trackNumbers = $searchResult->getColumnValues(ShipmentTrackInterface::TRACK_NUMBER);

            // cancel shipment orders at the api
            $api = $this->getApiGateway((int) $shipment->getStoreId());
            $apiResult = $api->cancelShipments($trackNumbers);

            $diff = array_diff($trackNumbers, $apiResult);
            if (!empty($diff)) {
                $bulkException->addError(__('Shipment orders %1 could not be cancelled.', implode(', ', $diff)));
                continue;
            }

            $this->labelStatusManagement->setLabelStatusPending($shipment->getOrder());

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
            throw new CouldNotDeleteException(__('An error occurred during shipment order cancellation.'), $bulkException);
        }
    }
}
