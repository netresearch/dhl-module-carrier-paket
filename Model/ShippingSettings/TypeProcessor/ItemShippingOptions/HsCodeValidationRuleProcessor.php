<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ItemShippingOptions;

use Dhl\Paket\Model\Util\UsCustomsTerritory;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
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
                if ($input->getCode() !== Codes::ITEM_INPUT_HS_CODE) {
                    continue;
                }

                if ($countryCode === 'CH') {
                    $this->addRules($input, ['required' => null]);
                }

                if (in_array($countryCode, UsCustomsTerritory::COUNTRY_CODES, true)) {
                    // US CBP requires a 10-digit HTSUS code per item for USA/Puerto Rico
                    $this->addRules($input, [
                        'required' => null,
                        'minLength' => 10,
                        'maxLength' => 10,
                        'validate-number' => null,
                    ]);
                }
            }

        }
        return $itemOptions;
    }

    /**
     * Merge validation rules into the input, keeping rules configured elsewhere.
     *
     * @param InputInterface $input The input to receive the rules.
     * @param array<string, mixed> $rules Rule name => rule param (null for parameterless rules).
     * @return void
     */
    private function addRules(InputInterface $input, array $rules): void
    {
        $validationRules = $input->getValidationRules();

        foreach ($rules as $name => $param) {
            $rule = $this->validationRuleFactory->create();
            $rule->setName($name);
            if ($param !== null) {
                $rule->setParam($param);
            }
            $validationRules[$name] = $rule;
        }

        $input->setValidationRules($validationRules);
    }
}
