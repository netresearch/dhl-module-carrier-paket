<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class VisualCheckOfAge
 *
 * @author  Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class VisualCheckOfAge implements OptionSourceInterface
{
    const OPTION_A16 = 'A16';
    const OPTION_A18 = 'A18';

    /**
     * Return array of options as value-label pairs
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 0,
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
