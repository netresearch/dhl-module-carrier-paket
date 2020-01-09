<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Util;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Utility to replace order property variables with their actual entity values.
 *
 * @author Andreas Müller <andreas.mueller@netresearch.de>
 * @link   https://www.netresearch.de
 */
class TemplateParser
{
    /**
     * @param OrderInterface $order
     * @param string $template
     * @return string
     */
    public function parse(OrderInterface $order, string $template): string
    {
        return str_replace(
            [
                '{{entity_id}}',
                '{{increment_id}}',
                '{{firstname}}',
                '{{lastname}}'
            ],
            [
                $order->getEntityId(),
                $order->getIncrementId(),
                $order->getCustomerFirstname(),
                $order->getCustomerLastname()
            ],
            $template
        );
    }
}
