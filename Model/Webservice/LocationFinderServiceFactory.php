<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Webservice;

use Dhl\Sdk\UnifiedLocationFinder\Api\LocationFinderServiceInterface;
use Dhl\Sdk\UnifiedLocationFinder\Api\ServiceFactoryInterface;
use Dhl\Sdk\UnifiedLocationFinder\Exception\ServiceException;
use Psr\Log\LoggerInterface;

class LocationFinderServiceFactory
{
    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ServiceFactoryInterface $serviceFactory, LoggerInterface $logger)
    {
        $this->serviceFactory = $serviceFactory;
        $this->logger = $logger;
    }

    /**
     * Create a pre-configured instance of the location finder service.
     *
     * @return LocationFinderServiceInterface
     * @throws ServiceException
     */
    public function create(): LocationFinderServiceInterface
    {
        return $this->serviceFactory->createLocationFinderService('pJDOxtJt03guK5eXKYcZt9Ez1bPi2Xvm', $this->logger);
    }
}
