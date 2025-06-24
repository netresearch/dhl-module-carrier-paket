<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\SplitAddress\SplittingRule;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Netresearch\ShippingCore\Api\Data\RecipientStreetInterface;
use Netresearch\ShippingCore\Api\SplitAddress\SplittingRuleInterface;
use Netresearch\ShippingCore\Model\SplitAddress\RecipientStreet;

/**
 * Fix addresses after address split.
 *
 * - Some Italian house numbers may be suffixed by an exponent number (delimited by slash).
 * - Some house numbers in Baden-Württemberg may be suffixed by a house number addition (delimited by slash).
 *
 * The exponent number/addition is split to the supplement field by the street splitter regex.
 * Unfortunately, although transmitted to the web service, the exponent number is missing
 * on the shipping label in some countries. As a workaround, we move the exponent number
 * back to the house number field.
 */
class RejoinSupplementRule implements SplittingRuleInterface
{
    #[\Override]
    public function apply(OrderAddressInterface $address, RecipientStreetInterface $recipientStreet): void
    {
        /** @var RecipientStreet $recipientStreet */
        $streetNumber = sprintf('%s/%s', $recipientStreet->getNumber(), $recipientStreet->getSupplement());
        $street = $address->getStreet();
        if (empty($street)) {
            return;
        }

        foreach ($street as $streetLine) {
            $streetLine = trim($streetLine);
            if (str_ends_with($streetLine, $streetNumber)) {
                $recipientStreet->setData(RecipientStreetInterface::NUMBER, $streetNumber);
                $recipientStreet->setData(RecipientStreetInterface::SUPPLEMENT, '');
                return;
            }
        }
    }
}
