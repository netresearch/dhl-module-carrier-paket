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
        $sandboxMode = $this->moduleConfig->isSandboxMode($storeId);

        if ($sandboxMode) {
            $appId = 'magento_1';
            $appToken = '2de26b775e59279464d1c2f8546432e62413372421c672db36eaacfc2f';
            $ekp = '2222222222';
        } else {
            $appId = 'M2_SHIPPING_1';
            $appToken = 'pMnRHKfNMw9O3qKMLAUhFT4cBbwotp';
            $ekp = $this->moduleConfig->getEkp($storeId);
        }

        return $this->checkoutServiceFactory->createCheckoutService(
            $appId,
            $appToken,
            $ekp,
            $this->logger,
            $sandboxMode
        );
    }
}
