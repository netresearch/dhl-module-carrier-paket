<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\UnifiedTracking\Api\Data\TrackingConfigurationInterface;
use Dhl\UnifiedTracking\Api\TrackingInfoProviderInterface;
use Psr\Log\LoggerInterface;

class TrackingConfiguration implements TrackingConfigurationInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns the carrier code.
     *
     * @return string
     */
    #[\Override]
    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getServiceName(): string
    {
        return TrackingInfoProviderInterface::SERVICE_PARCEL_DE;
    }

    /**
     * @return LoggerInterface
     */
    #[\Override]
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
