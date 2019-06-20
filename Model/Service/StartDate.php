<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Service;

use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;
use \DateTime;

/**
 * Class StartDate
 *
 * @package Dhl\Paket\Model\Service
 * @author   Andreas Müller <andreas.mueller@netresearch.de>
 */
class StartDate
{
    /**
     * @var TimezoneInterfaceFactory
     */
    private $timezoneFactory;

    /**
     * StartDate constructor.
     * @param TimezoneInterfaceFactory $timezoneFactory
     */
    public function __construct(
        TimezoneInterfaceFactory $timezoneFactory
    ) {
        $this->timezoneFactory = $timezoneFactory;
    }

    /**
     * Get actual date
     *
     * @return DateTime
     */
    public function getStartDate():DateTime
    {
        $timezone = $this->timezoneFactory->create();

        return $timezone->date();
    }
}
