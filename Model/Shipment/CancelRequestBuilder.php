<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Shipment;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterfaceFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment\TrackRepository;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection;

/**
 * Class CancelRequestBuilder
 *
 * For a given shipment, create one cancel request per associated track (=DHL Paket shipment number).
 *
 * @package Dhl\Paket\Model
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link https://www.netresearch.de/
 */
class CancelRequestBuilder
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
     * @var TrackRequestInterfaceFactory
     */
    private $requestFactory;

    /**
     * @var ShipmentInterface[]|\Magento\Sales\Model\Order\Shipment[]
     */
    private $shipments = [];

    /**
     * CancelRequestBuilder constructor.
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param TrackRepository $trackRepository
     * @param TrackRequestInterfaceFactory $requestFactory
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        TrackRepository $trackRepository,
        TrackRequestInterfaceFactory $requestFactory
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->trackRepository = $trackRepository;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Set the shipments to build the cancellation requests for.
     *
     * @param ShipmentInterface[] $shipments
     * @return void
     */
    public function setShipments(array $shipments)
    {
        $this->shipments = $shipments;
    }

    /**
     * Build the cancel requests.
     *
     * @return TrackRequestInterface[]
     */
    public function build(): array
    {
        $cancelRequests = [];

        $this->filterBuilder->setField(ShipmentTrackInterface::CARRIER_CODE);
        $this->filterBuilder->setConditionType('eq');
        $this->filterBuilder->setValue(Paket::CARRIER_CODE);
        $carrierCodeFilter = $this->filterBuilder->create();

        $getId = function (ShipmentInterface $shipment) {
            return $shipment->getEntityId();
        };
        $this->filterBuilder->setField(ShipmentTrackInterface::PARENT_ID);
        $this->filterBuilder->setConditionType('in');
        $this->filterBuilder->setValue(array_map($getId, $this->shipments));
        $shipmentIdFilter = $this->filterBuilder->create();

        // collect all tracks assigned to given shipments
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter($shipmentIdFilter);
        $searchCriteriaBuilder->addFilter($carrierCodeFilter);
        $searchCriteria = $searchCriteriaBuilder->create();

        /** @var Collection $trackCollection */
        $trackCollection = $this->trackRepository->getList($searchCriteria);

        /** @var Track $track */
        foreach ($trackCollection as $track) {
            try {
                $shipment = $track->getShipment();
            } catch (LocalizedException $exception) {
                // shipment no longer exists
                return [];
            }

            $cancelRequests[$track->getTrackNumber()]= $this->requestFactory->create([
                'storeId' => (int) $shipment->getStoreId(),
                'trackNumber' => (string) $track->getTrackNumber(),
                'salesShipment' => $shipment,
                'salesTrack' => $track,
            ]);
        }

        $this->shipments = [];

        return $cancelRequests;
    }
}
