<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentDate\Validator;

use Netresearch\ShippingCore\Api\ShipmentDate\DayValidatorInterface;

class ExcludeSundays implements DayValidatorInterface
{
    #[\Override]
    public function validate(\DateTimeInterface $dateTime, $store = null): bool
    {
        $weekDay = $dateTime->format('N');
        return ($weekDay !== '7');
    }
}
