<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\SplitAddress;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Netresearch\ShippingCore\Api\Data\RecipientStreetInterface;
use Netresearch\ShippingCore\Api\SplitAddress\RecipientStreetLoaderInterface;
use Netresearch\ShippingCore\Model\SplitAddress\RecipientStreet;

/**
 * Wrapper around the original street loader to apply country-based rules after splitting.
 */
class RecipientStreetLoader implements RecipientStreetLoaderInterface
{
    /**
     * @var RecipientStreetLoaderInterface
     */
    private $loader;

    public function __construct(RecipientStreetLoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Fix Italian addresses after address split.
     *
     * Some Italian house numbers may be suffixed by an exponent number (delimited by slash).
     * This exponent number is split to the supplement field by the street splitter regex.
     * Unfortunately, although transmitted to the web service, the exponent number is missing
     * on the shipping label. As a workaround, we move the exponent number back to the
     * house number field.
     *
     * @param OrderAddressInterface $address
     * @param RecipientStreetInterface|RecipientStreet $recipientStreet
     */
    private function applyItalyRules(OrderAddressInterface $address, RecipientStreetInterface $recipientStreet): void
    {
        $streetNumberWithExponent = sprintf('%s/%s', $recipientStreet->getNumber(), $recipientStreet->getSupplement());
        $street = $address->getStreet();
        if (empty($street)) {
            return;
        }

        foreach ($street as $streetLine) {
            $streetLine = trim($streetLine);
            if (substr($streetLine, -strlen($streetNumberWithExponent)) === $streetNumberWithExponent) {
                $recipientStreet->setData(RecipientStreetInterface::NUMBER, $streetNumberWithExponent);
                $recipientStreet->setData(RecipientStreetInterface::SUPPLEMENT, '');
                return;
            }
        }
    }

    public function load(OrderAddressInterface $address): RecipientStreetInterface
    {
        $recipientStreet = $this->loader->load($address);

        // apply special handling based on country
        if ($address->getCountryId() === 'IT') {
            $this->applyItalyRules($address, $recipientStreet);
        }

        return $recipientStreet;
    }
}
