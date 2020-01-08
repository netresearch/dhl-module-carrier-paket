<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentDate\Validator;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\ShipmentDate\DayValidatorInterface;

/**
 * Drop off days validator class. This class checks if the given date/time is a allowed drop off day.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 */
class DropOffDays implements DayValidatorInterface
{
    const WEEKDAY_SUNDAY = '7';

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * DropOffDays constructor.
     *
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(ModuleConfig $moduleConfig)
    {
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Returns TRUE if the date is a valid drop off day or FALSE otherwise.
     *
     * @param \DateTime $dateTime The date/time object to check
     * @param mixed $store The store to use for validation
     *
     * @return bool
     */
    public function validate(\DateTime $dateTime, $store = null): bool
    {
        $weekDay = $dateTime->format('N');
        $excludedDropOffDays = $this->moduleConfig->getExcludedDropOffDays($store);

        return !in_array($weekDay, $excludedDropOffDays, true) && ($weekDay !== self::WEEKDAY_SUNDAY);
    }
}
