<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;

class UpdatePreferredDayOptionLabelProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(OptionInterfaceFactory $optionFactory, TimezoneInterface $timezone)
    {
        $this->optionFactory = $optionFactory;
        $this->timezone = $timezone;
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

    /**
     * Create human readable labels for the preferred day options.
     *
     * Option labels are derived either from the Parcel Management API or from
     * the shipping option selection in database. In both cases, the values
     * are not formatted according to the user locale. This is done here.
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

        $serviceCode = Codes::SERVICE_OPTION_PREFERRED_DAY;

        $preferredDayOption = $shippingOptions[$serviceCode] ?? false;
        if (!$preferredDayOption) {
            // service not available for selection, nothing to modify.
            return $shippingOptions;
        }

        $dateInput = $this->getOptionInput($preferredDayOption, 'date');

        $dateOptions = $dateInput->getOptions();
        if (empty($dateOptions)) {
            // init option from default value
            $option = $this->optionFactory->create();
            $option->setValue($dateInput->getDefaultValue());
            $dateOptions[] = $option;
        }

        foreach ($dateOptions as $option) {
            if (empty($option->getValue())) {
                continue;
            }

            // set human readable label for all options
            $dateFormatted = $this->timezone->formatDate($option->getValue(), \IntlDateFormatter::MEDIUM);
            $option->setLabel($dateFormatted);
        }

        $dateInput->setOptions($dateOptions);

        return $shippingOptions;
    }
}
