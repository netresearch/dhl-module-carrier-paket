<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Util;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Address;

/**
 * Utility to replace order property variables with their actual entity values.
 *
 * @author Andreas Müller <andreas.mueller@netresearch.de>
 * @link   https://www.netresearch.de/
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
        $billingAddress = $order->getBillingAddress();

        if ($billingAddress instanceof Address) {
            $replace = [
                $order->getEntityId(),
                $order->getIncrementId(),
                $billingAddress->getFirstname(),
                $billingAddress->getLastname()
            ];
        } else {
            $replace = [
                $order->getEntityId(),
                $order->getIncrementId(),
                '',
                ''
            ];
        }

        return str_replace(['{{entity_id}}', '{{increment_id}}', '{{firstname}}', '{{lastname}}'], $replace, $template);
    }
}
