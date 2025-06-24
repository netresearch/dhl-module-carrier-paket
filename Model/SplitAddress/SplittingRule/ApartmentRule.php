<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\SplitAddress\SplittingRule;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Netresearch\ShippingCore\Api\Data\RecipientStreetInterface;
use Netresearch\ShippingCore\Api\SplitAddress\SplittingRuleInterface;

class ApartmentRule implements SplittingRuleInterface
{
    /**
     * Discard the street splitting result and split manually if two or more commas are included in the street address.
     *
     * @param OrderAddressInterface $address
     * @param RecipientStreetInterface $recipientStreet
     * @return void
     */
    #[\Override]
    public function apply(OrderAddressInterface $address, RecipientStreetInterface $recipientStreet): void
    {
        $street = implode(', ', $address->getStreet());
        if (substr_count($street, ',') < 2) {
            return;
        }

        $parts = array_map('trim', explode(',', $street));
        $recipientStreet->setData(RecipientStreetInterface::NAME, array_shift($parts));
        $recipientStreet->setData(RecipientStreetInterface::NUMBER, array_shift($parts));
        $recipientStreet->setData(RecipientStreetInterface::SUPPLEMENT, implode(', ', $parts));
    }
}
