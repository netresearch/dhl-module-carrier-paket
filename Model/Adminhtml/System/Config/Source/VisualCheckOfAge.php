<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class VisualCheckOfAge implements OptionSourceInterface
{
    public const OPTION_OFF = 'A00';
    public const OPTION_A16 = 'A16';
    public const OPTION_A18 = 'A18';

    /**
     * Return array of options as value-label pairs
     *
     * @return string[][]
     */
    #[\Override]
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::OPTION_OFF,
                'label' => __('No')
            ],
            [
                'value' => self::OPTION_A16,
                'label' => __('Minimum age +16 years'),
            ],
            [
                'value' => self::OPTION_A18,
                'label' => __('Minimum age +18 years'),
            ]
        ];
    }
}
