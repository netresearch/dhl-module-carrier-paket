<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Util;

/**
 * Destination countries covered by the US customs territory rules
 * (10-digit HTSUS code, pDDP thresholds): USA and Puerto Rico.
 *
 * Stock Magento models Puerto Rico as a US region only, so those addresses
 * are covered by the 'US' entry. The 'PR' entry covers shops that add
 * Puerto Rico as a custom country, a common workaround for the missing
 * directory entry (magento/magento2#7129).
 */
class UsCustomsTerritory
{
    public const COUNTRY_CODES = ['US', 'PR'];

    /**
     * The shipment request pipeline carries alpha-3 codes (Alpha3Converter in RequestExtractor).
     */
    public const COUNTRY_CODES_ALPHA3 = ['USA', 'PRI'];
}
