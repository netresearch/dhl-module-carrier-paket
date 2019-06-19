<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShipmentRequest\RequestModifier;
use Dhl\ShippingCore\Api\BulkLabelCreationInterface;
use Dhl\ShippingCore\Api\BulkShipmentConfigurationInterface;
use Dhl\ShippingCore\Api\RequestModifierInterface;

/**
 * Class BulkShipmentConfiguration
 *
 * @package Dhl\Paket\Model
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class BulkShipmentConfiguration implements BulkShipmentConfigurationInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var RequestModifier
     */
    private $requestModifier;

    /**
     * @var ShipmentManagement
     */
    private $shipmentManagement;

    /**
     * BulkShipmentConfiguration constructor.
     *
     * @param ModuleConfig $moduleConfig
     * @param RequestModifier $requestModifier
     * @param ShipmentManagement $shipmentManagement
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        RequestModifier $requestModifier,
        ShipmentManagement $shipmentManagement
    ) {
        $this->moduleConfig = $moduleConfig;
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
     * Obtain the service that connects to the DHL Paket label api.
     *
     * @return BulkLabelCreationInterface
     */
    public function getLabelService(): BulkLabelCreationInterface
    {
        return $this->shipmentManagement;
    }

    /**
     * Check if the customer should be notified after auto-creating the shipment (shipment confirmation email).
     *
     * @return bool
     */
    public function notify(): bool
    {
        return $this->moduleConfig->isNotificationEnabled();
    }
}
