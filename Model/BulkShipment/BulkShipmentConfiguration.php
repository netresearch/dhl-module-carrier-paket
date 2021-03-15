<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\BulkShipment;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\RequestModifier;
use Netresearch\ShippingCore\Api\BulkShipment\BulkLabelCancellationInterface;
use Netresearch\ShippingCore\Api\BulkShipment\BulkLabelCreationInterface;
use Netresearch\ShippingCore\Api\BulkShipment\BulkShipmentConfigurationInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestModifierInterface;

class BulkShipmentConfiguration implements BulkShipmentConfigurationInterface
{
    /**
     * @var RequestModifier
     */
    private $requestModifier;

    /**
     * @var ShipmentManagement
     */
    private $shipmentManagement;

    public function __construct(
        RequestModifier $requestModifier,
        ShipmentManagement $shipmentManagement
    ) {
        $this->requestModifier = $requestModifier;
        $this->shipmentManagement = $shipmentManagement;
    }

    /**
     * Obtain the carrier code which the current configuration applies to.
     *
     * @return string
     */
    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }

    /**
     * Obtain the Paket carrier's modifier to add carrier specific data to the shipment request.
     *
     * @return RequestModifierInterface
     */
    public function getRequestModifier(): RequestModifierInterface
    {
        return $this->requestModifier;
    }

    /**
     * Obtain the service that connects to the carrier's label api for creating labels.
     *
     * @return BulkLabelCreationInterface
     */
    public function getLabelService(): BulkLabelCreationInterface
    {
        return $this->shipmentManagement;
    }

    /**
     * Obtain the service that connects to the carrier's label api for cancelling labels.
     *
     * @return BulkLabelCancellationInterface
     */
    public function getCancellationService(): BulkLabelCancellationInterface
    {
        return $this->shipmentManagement;
    }

    /**
     * Check if DHL Paket allows deleting single tracks of a shipment.
     *
     * @return bool
     */
    public function isSingleTrackDeletionAllowed(): bool
    {
        return false;
    }
}
