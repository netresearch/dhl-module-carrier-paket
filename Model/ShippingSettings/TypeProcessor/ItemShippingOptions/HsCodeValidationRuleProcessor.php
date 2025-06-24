<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ItemShippingOptions;

use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\ValidationRuleInterfaceFactory;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ItemShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class HsCodeValidationRuleProcessor implements ItemShippingOptionsProcessorInterface
{
    /**
     * @var ValidationRuleInterfaceFactory
     */
    private ValidationRuleInterfaceFactory $validationRuleFactory;

    /**
     * @param ValidationRuleInterfaceFactory $validationRuleFactory
     */
    public function __construct(ValidationRuleInterfaceFactory $validationRuleFactory)
    {
        $this->validationRuleFactory = $validationRuleFactory;
    }

    /**
     * Processes item options to set validation rules for customs-related inputs on item level.
     *
     * @param string $carrierCode The code for the carrier.
     * @param array $itemOptions The list of item options to be processed.
     * @param int $storeId The ID of the store.
     * @param string $countryCode The destination country code.
     * @param string $postalCode The destination postal code.
     * @param ShipmentInterface|null $shipment The shipment instance, optional.
     * @return array The processed item options with updated validation rules.
     */
    #[\Override]
    public function process(
        string $carrierCode,
        array $itemOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ?ShipmentInterface $shipment = null
    ): array {

        foreach ($itemOptions as $itemOption) {
            $shippingOptions = $itemOption->getShippingOptions();
            $itemCustomsOption = $shippingOptions[Codes::ITEM_OPTION_CUSTOMS] ?? null;
            if (!$itemCustomsOption) {
                continue;
            }

            foreach ($itemCustomsOption->getInputs() as $input) {
                if ($input->getCode() === Codes::ITEM_INPUT_HS_CODE && $countryCode === 'CH') {
                    $requiredRule = $this->validationRuleFactory->create();
                    $requiredRule->setName('required');
                    $validationRules = $input->getValidationRules();
                    $validationRules['required'] = $requiredRule;
                    $input->setValidationRules($validationRules);

                }
            }

        }
        return $itemOptions;
    }
}
