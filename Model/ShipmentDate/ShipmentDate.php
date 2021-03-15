<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentDate;

use Dhl\Paket\Api\ShipmentDateInterface;
use Dhl\Paket\Model\Config\ModuleConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Netresearch\ShippingCore\Api\ShipmentDate\CutOffTimeConverterInterface;
use Netresearch\ShippingCore\Api\ShipmentDate\ShipmentDateCalculatorInterface;

class ShipmentDate implements ShipmentDateInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var CutOffTimeConverterInterface
     */
    private $cutOffTimeConverter;

    /**
     * @var ShipmentDateCalculatorInterface
     */
    private $shipmentDateCalculator;

    public function __construct(
        ModuleConfig $config,
        TimezoneInterface $timezone,
        CutOffTimeConverterInterface $cutOffTimeConverter,
        ShipmentDateCalculatorInterface $shipmentDateCalculator
    ) {
        $this->config = $config;
        $this->timezone = $timezone;
        $this->cutOffTimeConverter = $cutOffTimeConverter;
        $this->shipmentDateCalculator = $shipmentDateCalculator;
    }

    public function getDate($store = null): \DateTimeInterface
    {
        $cutOffTimes = $this->config->getCutOffTimes($store);
        $cutOffDates = $this->cutOffTimeConverter->convert($this->timezone->scopeDate($store), $cutOffTimes);

        try {
            return $this->shipmentDateCalculator->getDate($cutOffDates, $store);
        } catch (\RuntimeException $exception) {
            $message = __('Failed to calculate next possible shipment date. Please revise your cut-off times setting.');
            throw new LocalizedException($message);
        }
    }
}
