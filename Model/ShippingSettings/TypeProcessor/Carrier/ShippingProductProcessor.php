<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\Carrier;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes as ServiceCodes;
use Dhl\Paket\Model\Util\ShippingProducts;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\CarrierDataInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\CarrierDataProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class ShippingProductProcessor implements CarrierDataProcessorInterface
{
    /**
     * Remove WARENPOST NATIONAL product if Delivery Day service was selected.
     *
     * @param CarrierDataInterface $shippingSettings
     * @param int $storeId
     * @param string $countryCode
     * @param string $postalCode
     * @param ShipmentInterface|null $shipment
     * @return CarrierDataInterface
     */
    public function process(
        CarrierDataInterface $shippingSettings,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ShipmentInterface $shipment = null
    ): CarrierDataInterface {
        if ($shippingSettings->getCode() !== Paket::CARRIER_CODE) {
            // different carrier, nothing to modify.
            return $shippingSettings;
        }

        $serviceOptions = $shippingSettings->getServiceOptions();
        $isPrefDay = $serviceOptions[ServiceCodes::SERVICE_OPTION_PREFERRED_DAY] ?? false;
        if (!$isPrefDay) {
            // Delivery Day service not selected, proceed.
            return $shippingSettings;
        }

        foreach ($shippingSettings->getPackageOptions() as $packagingOption) {
            foreach ($packagingOption->getInputs() as $input) {
                if ($input->getCode() !== Codes::PACKAGE_INPUT_PRODUCT_CODE) {
                    // not the "shipping product" input, next.
                    continue;
                }

                $options = $input->getOptions();
                $index = array_search(ShippingProducts::CODE_WARENPOST_NATIONAL, array_column($options, 'value'));
                if ($index === false) {
                    // Warenpost product not available, proceed.
                    return $shippingSettings;
                }

                // remove Warenpost product from options
                unset($options[$index]);
                $input->setOptions($options);

                // update default value if necessary
                if ($input->getDefaultValue() === ShippingProducts::CODE_WARENPOST_NATIONAL) {
                    $defaultOption = array_shift($options);
                    $input->setDefaultValue($defaultOption['value']);
                }

                // all done, proceed.
                return $shippingSettings;
            }
        }

        return $shippingSettings;
    }
}
