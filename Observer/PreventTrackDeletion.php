<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Observer;

use Dhl\Paket\Model\Carrier\Paket;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order\Shipment\TrackRepository;

/**
 * Class PreventTrackDeletion
 *
 * DHL Paket tracks must not be deleted without cancelling the shipment.
 *
 * @package Dhl\Paket\Observer
 */
class PreventTrackDeletion implements ObserverInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var TrackRepository
     */
    private $trackRepository;

    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * PreventTrackDeletion constructor.
     *
     * @param TrackRepository $trackRepository
     * @param ActionFlag $actionFlag
     * @param SerializerInterface $serializer
     */
    public function __construct(
        TrackRepository $trackRepository,
        ActionFlag $actionFlag,
        SerializerInterface $serializer
    ) {
        $this->trackRepository = $trackRepository;
        $this->actionFlag = $actionFlag;
        $this->serializer = $serializer;
    }

    /**
     * Prohibit the deletion of individual shipment tracking numbers for DHL Paket shipments.
     *
     * Event:
     * - controller_action_predispatch_adminhtml_order_shipment_removeTrack
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getData('request');
        $trackId = (int) $request->getParam('track_id');

        $track = $this->trackRepository->get($trackId);
        if ($track->getCarrierCode() !== Paket::CARRIER_CODE) {
            return;
        }

        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
        $response = $this->serializer->serialize([
            'error' => true,
            'message' => __('Deleting a single DHL Paket tracking number is not supported. Please use the "Cancel Shipment" button on the shipment details page to cancel labels and tracks.')
        ]);

        /** @var \Magento\Shipping\Controller\Adminhtml\Order\Shipment\RemoveTrack $controller */
        $controller = $observer->getData('controller_action');

        /** @var \Magento\Framework\App\Response\Http $actionResponse */
        $actionResponse = $controller->getResponse();
        $actionResponse->representJson($response);
    }
}
