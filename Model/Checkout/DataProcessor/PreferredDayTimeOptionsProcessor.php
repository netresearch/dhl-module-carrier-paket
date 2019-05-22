<?php
/**
 * See LICENSE.md for license details.
 */

namespace Dhl\Paket\Model\Checkout\DataProcessor;

use Dhl\ShippingCore\Model\Checkout\AbstractProcessor;

class PreferredDayTimeOptionsProcessor extends AbstractProcessor
{
    /**
     * @param array $optionsData
     * @param string $countryId
     * @param string $postalCode
     * @param int|null $scopeId
     * @return array
     */
    public function processShippingOptions(
        array $optionsData,
        string $countryId,
        string $postalCode,
        int $scopeId = null
    ): array {
        if (isset($optionsData['preferredDay'])) {
            $optionsData['preferredDay']['inputs']['date']['options'] += $this->getPreferredDayOptions();
        }
        if (isset($optionsData['preferredTime'])) {
            $optionsData['preferredTime']['inputs']['time']['options'] += $this->getPreferredTimeOptions();
        }

        return $optionsData;
    }

    private function getPreferredDayOptions(): array
    {
        return [
            [
                'label' => 'Test 1',
                'value' => 'test1',
            ],
            [
                'label' => 'Test 2',
                'value' => 'test2',
            ],
            [
                'label' => 'Test 3',
                'value' => 'test3',
                'disabled' => true,
            ],
        ];
    }

    private function getPreferredTimeOptions(): array
    {
        return [
            [
                'label' => 'Test time 1',
                'value' => 'test1',
            ],
            [
                'label' => 'Test time 2',
                'value' => 'test2',
            ],
        ];
    }
}
