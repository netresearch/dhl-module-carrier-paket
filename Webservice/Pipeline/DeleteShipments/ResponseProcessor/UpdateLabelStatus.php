<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Pipeline\DeleteShipments\ResponseProcessor;

use Dhl\ShippingCore\Api\Data\TrackResponse\TrackErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Api\LabelStatusManagementInterface;
use Dhl\ShippingCore\Api\Pipeline\TrackResponseProcessorInterface;
use Magento\Sales\Model\Order\Shipment;

/**
 * Class UpdateLabelStatus
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class UpdateLabelStatus implements TrackResponseProcessorInterface
{
    /**
     * @var LabelStatusManagementInterface
     */
    private $labelStatusManagement;

    /**
     * UpdateLabelStatus constructor.
     *
     * @param LabelStatusManagementInterface $labelStatusManagement
     */
    public function __construct(LabelStatusManagementInterface $labelStatusManagement)
    {
        $this->labelStatusManagement = $labelStatusManagement;
    }

    /**
     * Mark orders with cancelled shipments "pending".
     *
     * @param TrackResponseInterface[] $trackResponses Shipment cancellation responses
     * @param TrackErrorResponseInterface[] $errorResponses Shipment cancellation errors
     */
    public function processResponse(array $trackResponses, array $errorResponses)
    {
        foreach ($trackResponses as $trackResponse) {
            /** @var Shipment $shipment */
            $shipment = $trackResponse->getSalesShipment();
            if ($shipment !== null) {
                $this->labelStatusManagement->setLabelStatusPending($shipment->getOrder());
            }
        }
    }
}
