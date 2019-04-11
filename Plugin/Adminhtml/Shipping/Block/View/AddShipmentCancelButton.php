<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Plugin\Adminhtml\Shipping\Block\View;

use Dhl\Paket\Model\Carrier\Paket;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Shipping\Block\Adminhtml\View;

/**
 * Class AddShipmentCancelButton
 *
 * @package Dhl\Paket\Plugin
 */
class AddShipmentCancelButton
{
    /**
     * Add a "Cancel Shipment" button to the shipment details page if the shipment has DHL Paket tracks.
     *
     * @param View $viewBlock
     * @return null
     */
    public function beforeSetLayout(View $viewBlock)
    {
        $tracks = $viewBlock->getShipment()->getAllTracks();
        $dhlTracks = array_filter($tracks, function (ShipmentTrackInterface $track) {
            return ($track->getCarrierCode() === Paket::CARRIER_CODE);
        });

        if (empty($dhlTracks)) {
            // no DHL Paket tracks in shipment
            return null;
        }

        $shipmentId = $viewBlock->getShipment()->getId();
        $cancelUrl = $viewBlock->getUrl('dhlpaket/shipment/cancel', ['shipment_id' => $shipmentId]);
        $viewBlock->addButton(
            'dhlpaket_cancel_shipment',
            [
                'label' => __('Cancel Shipment'),
                'onclick' => "setLocation('$cancelUrl')"
            ]
        );

        return null;
    }
}
