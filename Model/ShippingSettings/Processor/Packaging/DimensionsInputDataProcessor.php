<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Packaging;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\ValidationRuleInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Packaging\ShippingOptionsProcessorInterface;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * For dutiable routes (shipments with customs declaration), set weight and dimensions required.
 */
class DimensionsInputDataProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ValidationRuleInterfaceFactory
     */
    private $validationRuleFactory;

    public function __construct(ConfigInterface $config, ValidationRuleInterfaceFactory $validationRuleFactory)
    {
        $this->config = $config;
        $this->validationRuleFactory = $validationRuleFactory;
    }

    public function process(array $optionsData, ShipmentInterface $shipment): array
    {
        $order = $shipment->getOrder();
        $carrierCode = strtok((string)$order->getShippingMethod(), '_');

        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $optionsData;
        }

        if (!$this->config->isDutiableRoute($order->getShippingAddress()->getCountryId())) {
            return $optionsData;
        }

        $dimensionInputs = [
            Codes::PACKAGING_INPUT_LENGTH,
            Codes::PACKAGING_INPUT_WIDTH,
            Codes::PACKAGING_INPUT_HEIGHT,
            Codes::PACKAGING_INPUT_WEIGHT,
        ];

        $requiredRule = $this->validationRuleFactory->create();
        $requiredRule->setName('required');

        foreach ($optionsData as $optionGroup) {
            foreach ($optionGroup->getInputs() as $input) {
                if (\in_array($input->getCode(), $dimensionInputs, true)) {
                    $validationRules = $input->getValidationRules();
                    $validationRules['required'] = $requiredRule;
                    $input->setValidationRules($validationRules);
                }
            }
        }

        return $optionsData;
    }
}
