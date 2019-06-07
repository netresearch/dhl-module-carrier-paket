<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor;

use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\AbstractProcessor;

/**
 * Class PreferredDayTimeOptionsProcessor
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Max Melzer <max.melzer@netresearch.de>
 */
class PreferredDayTimeOptionsProcessor extends AbstractProcessor
{
    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * PreferredDayTimeOptionsProcessor constructor.
     *
     * @param OptionInterfaceFactory $optionFactory
     */
    public function __construct(OptionInterfaceFactory $optionFactory)
    {
        $this->optionFactory = $optionFactory;
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param string $countryId
     * @param string $postalCode
     * @param int|null $scopeId
     *
     * @return ShippingOptionInterface[]
     */
    public function processShippingOptions(
        array $optionsData,
        string $countryId,
        string $postalCode,
        int $scopeId = null
    ): array {
        foreach ($optionsData as $shippingOption) {
            if ($shippingOption->getCode() === 'preferredDay') {
                foreach ($shippingOption->getInputs() as $input) {
                    if ($input->getCode() === 'date') {
                        $input->setOptions($input->getOptions() + $this->getPreferredDayOptions());
                    }
                }
            }
            if ($shippingOption->getCode() === 'preferredTime') {
                foreach ($shippingOption->getInputs() as $input) {
                    if ($input->getCode() === 'time') {
                        $input->setOptions($input->getOptions() + $this->getPreferredTimeOptions());
                    }
                }
            }
        }

        return $optionsData;
    }

    /**
     * @return OptionInterface[]
     */
    private function getPreferredDayOptions(): array
    {

        return [
            $this->optionFactory->create([
                'label' => 'Test 1',
                'value' => 'test1',
            ]),
            $this->optionFactory->create([
                'label' => 'Test 2',
                'value' => 'test2',
            ]),
            $this->optionFactory->create([
                'label' => 'Test 3',
                'value' => 'test3',
                'disabled' => true,
            ]),
        ];
    }

    /**
     * @return OptionInterface[]
     */
    private function getPreferredTimeOptions(): array
    {
        return [
            $this->optionFactory->create([
                'label' => 'Test time 1',
                'value' => 'test1',
            ]),
            $this->optionFactory->create([
                'label' => 'Test time 2',
                'value' => 'test2',
            ]),
        ];
    }
}
