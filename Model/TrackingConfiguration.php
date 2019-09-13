<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\UnifiedTracking\Api\Data\TrackingConfigurationInterface;
use Dhl\UnifiedTracking\Api\TrackingInfoProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Class TrackingConfiguration
 *
 * @package Dhl\Paket\Model
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class TrackingConfiguration implements TrackingConfigurationInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * TrackingConfiguration constructor.

     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Returns the carrier code.
     *
     * @return string
     */
    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return TrackingInfoProviderInterface::SERVICE_PARCEL_DE;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
