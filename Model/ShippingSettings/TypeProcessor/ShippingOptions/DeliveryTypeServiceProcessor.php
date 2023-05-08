<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\Adminhtml\System\Config\Source\DeliveryType;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes as ServiceCodes;
use Dhl\Paket\Model\Util\ShippingProducts;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\OrderSelectionManager;

class DeliveryTypeServiceProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var OrderSelectionManager
     */
    private $selectionManager;

    public function __construct(OrderSelectionManager $selectionManager)
    {
        $this->selectionManager = $selectionManager;
    }

    private function getOptionInput(ShippingOptionInterface $shippingOption, string $inputCode): ?InputInterface
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === $inputCode) {
                return $input;
            }
        }

        return null;
    }

    /**
     * Remove all but DHL Paket International from the list of available products.
     *
     * @param InputInterface $input
     * @return void
     */
    private function updateShippingProductOptions(InputInterface $input): void
    {
        $options = array_filter(
            $input->getOptions(),
            function (OptionInterface $inputOption) {
                return ($inputOption->getValue() === ShippingProducts::CODE_INTERNATIONAL);
            }
        );
        $input->setOptions($options);
        $input->setDefaultValue(ShippingProducts::CODE_INTERNATIONAL);
    }

    /**
     * Set either CDP or ECONOMY and PREMIUM as service options.
     *
     * @param InputInterface $input
     * @return void
     */
    private function updateDeliveryTypeOptions(InputInterface $input): void
    {
        $defaultValue = $input->getDefaultValue();
        $options = array_filter(
            $input->getOptions(),
            function (OptionInterface $inputOption) use ($defaultValue) {
                return ($inputOption->getValue() === DeliveryType::OPTION_CDP) xor ($defaultValue !== DeliveryType::OPTION_CDP);
            }
        );

        $input->setOptions($options);
    }

    /**
     * Update shipping option values related to the Delivery Type service.
     *
     * If CDP was selected during checkout:
     * - limit available shipping products to V53WPAK
     * - remove ECONOMY and PREMIUM service options
     *
     * If CDP was not selected during checkout:
     * - remove CDP service option
     *
     * @param string $carrierCode
     * @param ShippingOptionInterface[] $shippingOptions
     * @param int $storeId
     * @param string $countryCode
     * @param string $postalCode
     * @param ShipmentInterface|null $shipment
     *
     * @return ShippingOptionInterface[]
     */
    public function process(
        string $carrierCode,
        array $shippingOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ShipmentInterface $shipment = null
    ): array {
        if ($carrierCode !== Paket::CARRIER_CODE) {
            // different carrier, nothing to modify.
            return $shippingOptions;
        }

        $optionCode = Codes::PACKAGE_OPTION_DETAILS;
        $packageDetails = $shippingOptions[$optionCode] ?? false;
        if ($packageDetails instanceof ShippingOptionInterface) {
            $selections = $this->selectionManager->load((int) $shipment->getShippingAddressId());
            $isCdpSelected = !empty(
                array_filter(
                    $selections,
                    function (SelectionInterface $selection) {
                        return (($selection->getShippingOptionCode() === ServiceCodes::SERVICE_OPTION_DELIVERY_TYPE)
                            && ($selection->getInputValue() === DeliveryType::OPTION_CDP));
                    }
                )
            );

            if ($isCdpSelected) {
                $input = $this->getOptionInput($packageDetails, Codes::PACKAGE_INPUT_PRODUCT_CODE);
                $this->updateShippingProductOptions($input);
            }
        }

        $serviceCode = ServiceCodes::SERVICE_OPTION_DELIVERY_TYPE;
        $deliveryType = $shippingOptions[$serviceCode] ?? false;
        if ($deliveryType instanceof ShippingOptionInterface) {
            $input = $this->getOptionInput($deliveryType, 'details');
            $this->updateDeliveryTypeOptions($input);
        }

        return $shippingOptions;
    }
}
