<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\LocationFinder\Api\Data\LocationInterface;
use Dhl\Sdk\LocationFinder\Api\LocationFinderServiceInterface;
use Dhl\Sdk\LocationFinder\Exception\ServiceException;
use Dhl\Sdk\LocationFinder\Service\ServiceFactory;
use Dhl\Sdk\LocationFinder\Exception\DetailedServiceException;
use Psr\Log\LoggerInterface;

/**
 * Class LocationFinderService
 *
 * @author  Andreas Müller <andreas.mueller@netresearch.de>
 * @link    https://www.netresearch.de
 */
class LocationFinderService
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ServiceFactory
     */
    private $locationFinderServiceFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var LocationFinderServiceInterface
     */
    private $locationFinderService;

    /**
     * LocationFinderService constructor.
     *
     * @param ModuleConfig $moduleConfig
     * @param ServiceFactory $locationFinderServiceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        ServiceFactory $locationFinderServiceFactory,
        LoggerInterface $logger
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->locationFinderServiceFactory = $locationFinderServiceFactory;
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
            $this->locationFinderService = $this->locationFinderServiceFactory->createLocationFinderService(
                $this->moduleConfig->getAuthUsername(),
                $this->moduleConfig->getAuthPassword(),
                $this->logger,
                $this->moduleConfig->isSandboxMode($this->storeId)
            );
        }

        return $this->locationFinderService;
    }

    /**
     * @param string $countryCode
     * @param string $zip
     * @param string $city
     * @param string|null $streetName
     * @param string|null $streetNo
     * @return LocationInterface[]
     * @throws DetailedServiceException
     * @throws ServiceException
     */
    public function getLocations(
        string $countryCode,
        string $zip,
        string $city,
        string $streetName = null,
        string $streetNo = null
    ): array {
        return $this->getLocationFinderService()->getPickUpLocations($countryCode, $zip, $city, $streetName, $streetNo);
    }
}
