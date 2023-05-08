<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DeliveryType implements OptionSourceInterface
{
    public const OPTION_ECONOMY = 'ECONOMY';
    public const OPTION_PREMIUM = 'PREMIUM';
    public const OPTION_CDP = 'CDP';

    /**
     * Return array of options as value-label pairs.
     *
     * Note that the CDP delivery type is only added implicitly to the
     * available options if the service was selected during checkout.
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::OPTION_ECONOMY,
                'label' => 'Economy',
            ],
            [
                'value' => self::OPTION_PREMIUM,
                'label' => 'Premium',
            ]
        ];
    }
}
