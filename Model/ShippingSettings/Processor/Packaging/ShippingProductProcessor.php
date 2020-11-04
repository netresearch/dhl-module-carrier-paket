<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Packaging;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes as CheckoutServiceCodes;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\Data\ShippingSettings\CarrierDataInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Checkout\GlobalProcessorInterface;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class ShippingProductProcessor implements GlobalProcessorInterface
{
    /**
     * Remove WARENPOST NATIONAL product if Delivery Day service was selected.
     *
     * @param CarrierDataInterface $carrierData
     * @return CarrierDataInterface
     */
    public function process(CarrierDataInterface $carrierData): CarrierDataInterface
    {
        $serviceOptions = $carrierData->getServiceOptions();
        $isPrefDay = $serviceOptions[CheckoutServiceCodes::CHECKOUT_SERVICE_PREFERRED_DAY] ?? false;
        if (!$isPrefDay) {
            // Delivery Day service not selected, proceed.
            return $carrierData;
        }

        foreach ($carrierData->getPackageOptions() as $packagingOption) {
            foreach ($packagingOption->getInputs() as $input) {
                if ($input->getCode() !== Codes::PACKAGING_INPUT_PRODUCT_CODE) {
                    // not the "shipping product" input, next.
                    continue;
                }

                $options = $input->getOptions();
                $index = array_search(ShippingProducts::CODE_WARENPOST_NATIONAL, array_column($options, 'value'));
                if ($index === false) {
                    // Warenpost product not available, proceed.
                    return $carrierData;
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
                return $carrierData;
            }
        }

        return $carrierData;
    }
}
