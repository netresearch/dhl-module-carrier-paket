<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\ArrayProcessor;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\ArrayProcessor\ShippingSettingsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\PackagingDataProvider;

class NoNeighborDeliveryDefaultConfigValueProcessor implements ShippingSettingsProcessorInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    public function __construct(ModuleConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Set the default config value for the No Neighbor Delivery service unless selection is delegated to checkout.
     *
     * @param mixed[] $shippingSettings
     * @param int $storeId
     * @param ShipmentInterface|null $shipment
     *
     * @return mixed[]
     */
    #[\Override]
    public function process(array $shippingSettings, int $storeId, ?ShipmentInterface $shipment = null): array
    {
        if ($this->config->isNoNeighborDeliveryEnabled($storeId)) {
            // service is available for selection in checkout, not setting admin default config value.
            return $shippingSettings;
        };

        $serviceCode = Codes::SERVICE_OPTION_NO_NEIGHBOR_DELIVERY;
        foreach ($shippingSettings['carriers'] as $carrierCode => &$carrierData) {
            if ($carrierCode !== Paket::CARRIER_CODE) {
                continue;
            }

            if (!isset($carrierData[PackagingDataProvider::GROUP_SERVICE][$serviceCode])) {
                // No Neighbor Delivery not available for the current shipment.
                return $shippingSettings;
            }

            $defaultConfigValue = ModuleConfig::CONFIG_PATH_EXCLUDE_NEIGHBOR_DELIVERY;
            $serviceOption = &$carrierData[PackagingDataProvider::GROUP_SERVICE][$serviceCode];
            $serviceOption['inputs']['enabled']['defaultConfigValue'] = $defaultConfigValue;
        }

        return $shippingSettings;
    }
}
