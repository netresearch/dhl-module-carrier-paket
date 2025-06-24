<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\AdditionalFee;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Api\AdditionalFee\AdditionalFeeProviderInterface;

class AdditionalFeeProvider implements AdditionalFeeProviderInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    public function __construct(ModuleConfig $config)
    {
        $this->config = $config;
    }

    #[\Override]
    public function getAmounts(int $storeId): array
    {
        return [
            Codes::SERVICE_OPTION_DELIVERY_TYPE => $this->config->getClosestDropPointAdditionalCharge($storeId),
            Codes::SERVICE_OPTION_PREFERRED_DAY => $this->config->getPreferredDayAdditionalCharge($storeId),
            Codes::SERVICE_OPTION_NO_NEIGHBOR_DELIVERY => $this->config->getNoNeighborDeliveryAdditionalCharge($storeId)
        ];
    }
}
