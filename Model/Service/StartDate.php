<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterfaceFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;

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
     * @var ResolverInterfaceFactory
     */
    private $localResolverFactory;

    /**
     * StartDate constructor.
     * @param TimezoneInterfaceFactory $timezoneFactory
     * @param ResolverInterfaceFactory $localResolverFactory
     */
    public function __construct(
        TimezoneInterfaceFactory $timezoneFactory,
        ResolverInterfaceFactory $localResolverFactory
    ) {
        $this->timezoneFactory = $timezoneFactory;
        $this->localResolverFactory = $localResolverFactory;
    }

    /**
     * @param int $storeId
     * @return \DateTime
     */
    public function getStartDate(int $storeId)
    {
        $timezone = $this->timezoneFactory->create();
        $currentDateTime = $timezone->date();

        return $currentDateTime;
    }
}
