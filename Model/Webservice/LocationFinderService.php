<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\UnifiedLocationFinder\Api\LocationFinderServiceInterface;
use Dhl\Sdk\UnifiedLocationFinder\Exception\ServiceException;
use Dhl\Sdk\UnifiedLocationFinder\Service\ServiceFactory;
use Psr\Log\LoggerInterface;

/**
 * Wrap SDK service, add credentials and maximum radius, limit.
 *
 * @author Andreas Müller <andreas.mueller@netresearch.de>
 * @link   https://www.netresearch.de/
 */
class LocationFinderService implements LocationFinderServiceInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LocationFinderServiceInterface|null
     */
    private $locationFinderService;

    /**
     * LocationFinderService constructor.
     *
     * @param ModuleConfig $coreConfig
     * @param ServiceFactory $serviceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleConfig $coreConfig,
        ServiceFactory $serviceFactory,
        LoggerInterface $logger
    ) {
        $this->config = $coreConfig;
        $this->serviceFactory = $serviceFactory;
        $this->logger = $logger;
    }

    /**
     * Obtain an instance of the Location Finder API service.
     *
     * @return LocationFinderServiceInterface
     * @throws ServiceException
     */
    private function getLocationFinderService(): LocationFinderServiceInterface
    {
        if ($this->locationFinderService === null) {
            $this->locationFinderService = $this->serviceFactory->createLocationFinderService(
                $this->config->getLocationFinderConsumerKey(),
                $this->logger
            );
        }

        return $this->locationFinderService;
    }

    public function getPickUpLocations(
        string $countryCode,
        string $postalCode = '',
        string $city = '',
        string $street = '',
        string $service = self::SERVICE_PARCEL,
        int $radius = null,
        int $limit = null
    ): array {
        return $this->getLocationFinderService()->getPickUpLocations(
            $countryCode,
            $postalCode,
            $city,
            $street,
            $service,
            $radius ?: 15000,
            $limit ?: 50
        );
    }

    public function getPickUpLocationsByCoordinate(
        float $latitude,
        float $longitude,
        string $service = self::SERVICE_PARCEL,
        int $radius = null,
        int $limit = null
    ): array {
        return $this->getLocationFinderService()->getPickUpLocationsByCoordinate(
            $latitude,
            $longitude,
            $service,
            $radius ?: 15000,
            $limit ?: 50
        );
    }

    public function getDropOffLocations(
        string $countryCode,
        string $postalCode = '',
        string $city = '',
        string $street = '',
        string $service = self::SERVICE_PARCEL,
        int $radius = null,
        int $limit = null
    ): array {
        return $this->getLocationFinderService()->getDropOffLocations(
            $countryCode,
            $postalCode,
            $city,
            $street,
            $service,
            $radius ?: 15000,
            $limit ?: 50
        );
    }

    public function getDropOffLocationsByCoordinate(
        float $latitude,
        float $longitude,
        string $service = self::SERVICE_PARCEL,
        int $radius = null,
        int $limit = null
    ): array {
        return $this->getLocationFinderService()->getDropOffLocationsByCoordinate(
            $latitude,
            $longitude,
            $service,
            $radius ?: 15000,
            $limit ?: 50
        );
    }
}
