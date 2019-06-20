<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class VisualCheckOfAge
 *
 * @package Dhl\Paket\Model
 * @author  Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class VisualCheckOfAge implements ArrayInterface
{
    const OPTION_A16 = 'A16';
    const OPTION_A18 = 'A18';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $optionsArray = [
            [
                'value' => 0,
                'label' => __('No')
            ],
            [
                'value' => self::OPTION_A16,
                'label' => self::OPTION_A16,
            ],
            [
                'value' => self::OPTION_A18,
                'label' => self::OPTION_A18,
            ]
        ];

        return $optionsArray;
    }
}
