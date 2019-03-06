<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Dhl\Paket\Model\ShippingProducts\ShippingProducts;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Procedure
 *
 * @package Dhl\Paket\Model
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Procedure implements ArrayInterface
{
    /**
     * Options getter.
     *
     * @return mixed[]
     */
    public function toOptionArray(): array
    {
        $optionArray = [];
        $options     = $this->toArray();

        foreach ($options as $value => $label) {
            $optionArray[] = [
                // Casting is required as the value otherwise may be interpreted as integer!
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        return $optionArray;
    }

    /**
     * Get options in "key-value" format.
     *
     * @fixme(nr): product definitions moved to carrier module
     * @return string[]
     */
    private function toArray(): array
    {
        return [
            ShippingProducts::PROCEDURE_NATIONAL            => __('DHL Paket: V01PAK'),
            ShippingProducts::PROCEDURE_NATIONAL_TAGGLEICH  => __('DHL Paket Taggleich: V06PAK'),
            ShippingProducts::PROCEDURE_INTERNATIONAL       => __('DHL Paket International: V53WPAK'),
            ShippingProducts::PROCEDURE_EUROPAKET           => __('DHL Europaket: V54EPAK'),
            ShippingProducts::PROCEDURE_CONNECT             => __('DHL Paket Connect: V55PAK'),
            ShippingProducts::PROCEDURE_PAKET_AUSTRIA       => __('DHL Paket Austria: V86PARCEL'),
            ShippingProducts::PROCEDURE_PAKET_CONNECT       => __('DHL Paket Connect: V87PARCEL'),
            ShippingProducts::PROCEDURE_PAKET_INTERNATIONAL => __('DHL Paket International: V82PARCEL'),
        ];
    }
}
