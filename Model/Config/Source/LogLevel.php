<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Config\Source;

use Magento\Framework\Logger\Monolog;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class LogLevel
 *
 * @author    Ronny Gertler <ronny.gertler@netresearch.de>
 * @link      http://www.netresearch.de/
 */
class LogLevel implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => (string) Monolog::ERROR, 'label' => __('Errors')],
            ['value' => (string) Monolog::WARNING,'label' => __('Warnings')],
            ['value' => (string) Monolog::INFO, 'label' => __('Info (All API Activities)')],
        ];
    }
}
