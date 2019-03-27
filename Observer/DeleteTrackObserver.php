<?php
/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Observer;

use Dhl\Paket\Model\Carrier\Paket;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class DeleteTrackObserver
 * @package Dhl\Paket\Observer
 */
class DeleteTrackObserver implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * DeleteTrackObserver constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Prohibit the deletion if individual shipment tracking numbers
     * for DHL Paket shipments by throwing an exception.
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
        $track = $observer->getData('track');
        if ($track->getCarrierCode() !== Paket::CARRIER_CODE) {
            return;
        }
        $message = 'Deleting a single DHL tracking number is not supported. '
            . 'Please use the "Cancel" button on the Shipment view page to '
            . 'cancel the DHL labels and shipment orders associated with a shipment.';
        $this->logger->warning(
            $message,
            ['orderId' => $track->getOrderId(), 'trackNum' => $track->getTrackNumber()]
        );

        throw new LocalizedException(__($message));
    }
}
