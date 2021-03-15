<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\ParcelManagement\Api\CheckoutServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Api\ServiceFactoryInterface;
use Dhl\Sdk\Paket\ParcelManagement\Exception\ServiceException;
use Psr\Log\LoggerInterface;

class ParcelManagementServiceFactory
{
    /**
     * @var ServiceFactoryInterface
     */
    private $checkoutServiceFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ServiceFactoryInterface $checkoutServiceFactory,
        ModuleConfig $moduleConfig,
        LoggerInterface $logger
    ) {
        $this->checkoutServiceFactory = $checkoutServiceFactory;
        $this->moduleConfig = $moduleConfig;
        $this->logger = $logger;
    }

    /**
     * Create a pre-configured instance of the parcel management service.
     *
     * @param int $storeId
     * @return CheckoutServiceInterface
     * @throws ServiceException
     */
    public function create(int $storeId): CheckoutServiceInterface
    {
        return $this->checkoutServiceFactory->createCheckoutService(
            $this->moduleConfig->getAuthUsername(),
            $this->moduleConfig->getAuthPassword(),
            $this->moduleConfig->getEkp($storeId),
            $this->logger,
            $this->moduleConfig->isSandboxMode($storeId)
        );
    }
}
