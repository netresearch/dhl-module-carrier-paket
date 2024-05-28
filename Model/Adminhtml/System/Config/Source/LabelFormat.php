<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Dhl\Sdk\ParcelDe\Shipping\Api\Data\OrderConfigurationInterface;
use Magento\Framework\Data\OptionSourceInterface;

class LabelFormat implements OptionSourceInterface
{
    public const OPTION_DEFAULT = '';
    public const OPTION_A4 = OrderConfigurationInterface::PRINT_FORMAT_A4;
    public const OPTION_105X205 = OrderConfigurationInterface::PRINT_FORMAT_910_300_700;
    public const OPTION_105X205OZ = OrderConfigurationInterface::PRINT_FORMAT_910_300_700_OZ;
    public const OPTION_105X148 = OrderConfigurationInterface::PRINT_FORMAT_910_300_300;
    public const OPTION_105X148OZ = OrderConfigurationInterface::PRINT_FORMAT_910_300_300_OZ;
    public const OPTION_105X208 = OrderConfigurationInterface::PRINT_FORMAT_910_300_710;
    public const OPTION_103X199 = OrderConfigurationInterface::PRINT_FORMAT_910_300_600;
    public const OPTION_103X150 = OrderConfigurationInterface::PRINT_FORMAT_910_300_400;
    public const OPTION_100X70 = OrderConfigurationInterface::PRINT_FORMAT_100X70;

    /**
     * Return array of options as value-label pairs.
     *
     * phpcs:disable Generic.Files.LineLength.TooLong
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::OPTION_DEFAULT,
                'label' => __('--Please Select--'),
            ],
            [
                'value' => self::OPTION_A4,
                'label' => __('Common label laser printing A4 standard paper'),
            ],
            [
                'value' => self::OPTION_105X205,
                'label' => __('Common label laser printing 105 x 205 mm (DIN A5 standard paper, 910-300-700)'),
            ],
            [
                'value' => self::OPTION_105X205OZ,
                'label' => __('Common label laser printing 105 x 205 mm (DIN A5 standard paper, 910-300-700) without additional barcode labels'),
            ],
            [
                'value' => self::OPTION_105X148,
                'label' => __('Common label laser printing 105 x 148 mm (DIN-A5 standard paper, 910-300-300)'),
            ],
            [
                'value' => self::OPTION_105X148OZ,
                'label' => __('Common label laser printing 105 x 148 mm (DIN A5 standard paper, 910-300-300) without additional barcode labels'),
            ],
            [
                'value' => self::OPTION_105X208,
                'label' => __('Common label laser printing 105 x 208 mm (910-300-710)'),
            ],
            [
                'value' => self::OPTION_103X199,
                'label' => __('Common label thermal printing 103 x 199 mm (910-300-600, 910-300-610)'),
            ],
            [
                'value' => self::OPTION_103X150,
                'label' => __('Common label thermal printing 103 x 150 mm (910-300-400, 910-300-410)'),
            ],
        ];
    }
}
