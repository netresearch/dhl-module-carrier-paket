<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Validator;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes as ServiceCodes;
use Dhl\Paket\Model\Util\ShippingProducts;
use Magento\Framework\Exception\ValidatorException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestValidatorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class ProductValidator implements RequestValidatorInterface
{
    private function canShipWithWarenpost(Request $request): bool
    {
        $hasPrefDayService = false;
        $hasCodService = false;
        $isWarenpost = false;

        $packages = $request->getData('packages');
        foreach ($packages as $package) {
            $isWarenpost = $package['params']['shipping_product'] === ShippingProducts::CODE_WARENPOST_NATIONAL;

            $serviceData = $package['params']['services'][ServiceCodes::SERVICE_OPTION_PREFERRED_DAY] ?? [];
            $hasPrefDayService = $hasPrefDayService || ($serviceData['enabled'] ?? false);

            $serviceData = $package['params']['services'][Codes::SERVICE_OPTION_CASH_ON_DELIVERY] ?? [];
            $hasCodService = $hasCodService || ($serviceData['enabled'] ?? false);
        }

        return !$isWarenpost || (!$hasPrefDayService && !$hasCodService);
    }

    public function validate(Request $shipmentRequest): void
    {
        if (!$this->canShipWithWarenpost($shipmentRequest)) {
            throw new ValidatorException(
                __('Warenpost does not support Cash on Delivery or Delivery Day service. Please change the shipping product or deselect the service(s).')
            );
        }
    }
}
