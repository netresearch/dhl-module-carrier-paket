<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Retrieve the next possible shipment date for DHL Parcel Germany orders.
 *
 * The current time is considered as well as the courier handover times configured in config.
 */
interface ShipmentDateInterface
{
    /**
     * Obtain next possible shipment date.
     *
     * @param mixed $store
     *
     * @return \DateTimeInterface
     * @throws LocalizedException
     */
    public function getDate($store = null): \DateTimeInterface;
}
