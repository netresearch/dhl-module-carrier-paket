<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Model\Config\Source\TermsOfTrade;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CommentInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\ValidationRuleInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes as CarrierOptionCodes;

class CustomsInputsProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var TermsOfTrade
     */
    private $termsOfTrade;

    /**
     * @var ShippingConfigInterface
     */
    private $shippingConfig;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * @var CommentInterfaceFactory
     */
    private $commentFactory;

    /**
     * @var ValidationRuleInterfaceFactory
     */
    private $validationRuleFactory;

    public function __construct(
        TermsOfTrade $termsOfTrade,
        ShippingConfigInterface $shippingConfig,
        ModuleConfig $config,
        OptionInterfaceFactory $optionFactory,
        CommentInterfaceFactory $commentFactory,
        ValidationRuleInterfaceFactory $validationRuleFactory
    ) {
        $this->termsOfTrade = $termsOfTrade;
        $this->shippingConfig = $shippingConfig;
        $this->config = $config;
        $this->optionFactory = $optionFactory;
        $this->commentFactory = $commentFactory;
        $this->validationRuleFactory = $validationRuleFactory;
    }

    private function getOptionInput(ShippingOptionInterface $serviceOption, string $inputCode): ?InputInterface
    {
        foreach ($serviceOption->getInputs() as $input) {
            if ($input->getCode() === $inputCode) {
                return $input;
            }
        }

        return null;
    }

    private function addTermsOfTradeOptions(ShippingOptionInterface $customsOption)
    {
        $input = $this->getOptionInput($customsOption, CarrierOptionCodes::PACKAGE_INPUT_TERMS_OF_TRADE);
        if (!$input instanceof InputInterface) {
            return;
        }

        $fnCreateOptions = function (array $optionArray) {
            $option = $this->optionFactory->create();
            $option->setValue((string) $optionArray['value']);
            $option->setLabel((string) $optionArray['label']);
            return $option;
        };

        $input->setOptions(array_map($fnCreateOptions, $this->termsOfTrade->toOptionArray()));
    }

    private function updateDimensionsInputs(
        ShippingOptionInterface $customsOption,
        OrderInterface $order
    ): void {
        if (!$this->shippingConfig->isDutiableRoute($order->getShippingAddress()->getCountryId())) {
            return;
        }

        $dimensionInputs = [
            Codes::PACKAGE_INPUT_LENGTH => $this->getOptionInput($customsOption, Codes::PACKAGE_INPUT_LENGTH),
            Codes::PACKAGE_INPUT_WIDTH => $this->getOptionInput($customsOption, Codes::PACKAGE_INPUT_WIDTH),
            Codes::PACKAGE_INPUT_HEIGHT => $this->getOptionInput($customsOption, Codes::PACKAGE_INPUT_HEIGHT),
            Codes::PACKAGE_INPUT_WEIGHT => $this->getOptionInput($customsOption, Codes::PACKAGE_INPUT_WEIGHT),
        ];

        $requiredRule = $this->validationRuleFactory->create();
        $requiredRule->setName('required');

        foreach ($dimensionInputs as $input) {
            if ($input instanceof InputInterface) {
                $validationRules = $input->getValidationRules();
                $validationRules['required'] = $requiredRule;
                $input->setValidationRules($validationRules);
            }
        }
    }

    private function updatePostalChargesInput(
        ShippingOptionInterface $customsOption,
        OrderInterface $order
    ): void {
        $input = $this->getOptionInput($customsOption, 'customsFees');
        if (!$input instanceof InputInterface) {
            return;
        }

        $store = $order->getStore();
        $currency = $store->getBaseCurrency();
        $currencySymbol = $currency->getCurrencySymbol() ?: $currency->getCode();
        $comment = $this->commentFactory->create();
        $comment->setContent($currencySymbol);
        $input->setComment($comment);

        $baseShippingInclTax = (float) $order->getBaseShippingInclTax();
        $baseShippingRefunded = (float) $order->getBaseShippingRefunded();
        $baseShippingTaxRefunded = (float) $order->getBaseShippingTaxRefunded();
        $shippingCost = $baseShippingInclTax - $baseShippingRefunded - $baseShippingTaxRefunded;
        $input->setDefaultValue((string) $shippingCost);
    }

    private function updateSendersReferenceInput(
        ShippingOptionInterface $customsOption,
        OrderInterface $order
    ): void {
        $input = $this->getOptionInput($customsOption, 'sendersCustomsReference');
        if (!$input instanceof InputInterface) {
            return;
        }

        $destinationCountry = $order->getShippingAddress()->getCountryId();
        $referenceNumbers = $this->config->getCustomsReferenceNumbers($order->getStoreId());
        $value = $referenceNumbers[$destinationCountry] ?? '';
        $input->setDefaultValue($value);
    }

    /**
     * Update cross-border inputs for dutiable shipments.
     *
     * - set dimensions inputs required
     * - add the currency symbol to the "postal charges / fees" input
     * - set default value for the "postal charges / fees" input
     * - set default value for the "senders customs reference" input
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

        if (!$shipment) {
            // checkout scope, nothing to modify.
            return $shippingOptions;
        }

        $optionCode = Codes::PACKAGE_OPTION_CUSTOMS;
        $customsOption = $shippingOptions[$optionCode] ?? false;
        if (!$customsOption instanceof ShippingOptionInterface) {
            // not the package customs option, proceed.
            return $shippingOptions;
        }

        $order = $shipment->getOrder();
        $this->addTermsOfTradeOptions($customsOption);
        $this->updateDimensionsInputs($customsOption, $order);
        $this->updatePostalChargesInput($customsOption, $order);
        $this->updateSendersReferenceInput($customsOption, $order);

        return $shippingOptions;
    }
}
