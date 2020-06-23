<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Validator;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\Pipeline\ShipmentRequest\RequestValidatorInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Shipping\Model\Shipment\Request;

class ProductValidator implements RequestValidatorInterface
{
    private function canShipWithWarenpost(Request $request)
    {
        $hasPrefDayService = false;
        $hasCodService = false;
        $isWarenpost = false;

        $packages = $request->getData('packages');
        foreach ($packages as $package) {
            $isWarenpost = $package['params']['shipping_product'] === ShippingProducts::CODE_WARENPOST_NATIONAL;

            $serviceData = $package['params']['services'][Codes::CHECKOUT_SERVICE_PREFERRED_DAY] ?? [];
            $hasPrefDayService = $hasPrefDayService || ($serviceData['enabled'] ?? false);

            $serviceData = $package['params']['services'][Codes::CHECKOUT_SERVICE_CASH_ON_DELIVERY] ?? [];
            $hasCodService = $hasCodService || ($serviceData['enabled'] ?? false);
        }

        return !$isWarenpost || (!$hasPrefDayService && !$hasCodService);
    }

    public function validate(Request $shipmentRequest)
    {
        if (!$this->canShipWithWarenpost($shipmentRequest)) {
            throw new ValidatorException(
                __('Warenpost does not support Cash on Delivery or Preferred Day service. Please change the shipping product or deselect the service(s).')
            );
        }
    }
}
