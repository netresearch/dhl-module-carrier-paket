<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\DayValidator;

use DateTime;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\DayValidatorInterface;
use Magento\Sales\Model\Order;

/**
 * Drop off days validator class. This class checks if the given date/time is a allowed drop off day.
 *
 * @package Dhl\Paket\Model\DayFilter
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
    public function __construct(
        ModuleConfig $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Returns TRUE if the date is a valid drop off day or FALSE otherwise.
     *
     * @param Order    $order    The current order
     * @param DateTime $dateTime The date/time object to check
     *
     * @return bool
     */
    public function validate(Order $order, DateTime $dateTime): bool
    {
        $weekDay             = $dateTime->format('N');
        $excludedDropOffDays = $this->moduleConfig->getExcludedDropOffDays($order->getStoreId());

        return !in_array($weekDay, $excludedDropOffDays, true)
            && ($weekDay !== self::WEEKDAY_SUNDAY);
    }
}
