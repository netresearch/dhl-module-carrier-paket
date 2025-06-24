<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Endorsement implements OptionSourceInterface
{
    public const OPTION_RETURN = 'RETURN';
    public const OPTION_ABANDON = 'ABANDON';

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
                'value' => self::OPTION_RETURN,
                'label' => __('Return immediately')
            ],
            [
                'value' => self::OPTION_ABANDON,
                'label' => __('Abandon (free of charge)'),
            ],
        ];
    }
}
