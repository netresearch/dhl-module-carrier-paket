<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\ParcelManagement\Api\CheckoutServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Api\Data\CarrierServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Api\ServiceFactoryInterface;
use Dhl\Sdk\Paket\ParcelManagement\Exception\AuthenticationException;
use Dhl\Sdk\Paket\ParcelManagement\Exception\ClientException;
use Dhl\Sdk\Paket\ParcelManagement\Exception\ServerException;
use Psr\Log\LoggerInterface;

/**
 * Class ParcelManagementService
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ParcelManagementService implements CheckoutServiceInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ServiceFactoryInterface
     */
    private $checkoutServiceFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int|null
     */
    private $storeId;

    /**
     * @var CheckoutServiceInterface|null
     */
    private $checkoutService;

    /**
     * ParcelManagementService constructor.
     *
     * @param ModuleConfig $moduleConfig
     * @param LoggerInterface $logger
     * @param int $storeId
     * @param ServiceFactoryInterface $checkoutServiceFactory
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        ServiceFactoryInterface $checkoutServiceFactory,
        LoggerInterface $logger,
        int $storeId = null
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->checkoutServiceFactory = $checkoutServiceFactory;
        $this->logger = $logger;
        $this->storeId = $storeId;
    }

    /**
     * Obtain an instance of the Parcel Management API checkout service.
     *
     * @return CheckoutServiceInterface
     */
    private function getCheckoutService(): CheckoutServiceInterface
    {
        if ($this->checkoutService === null) {
            $this->checkoutService = $this->checkoutServiceFactory->createCheckoutService(
                $this->moduleConfig->getAuthUsername(),
                $this->moduleConfig->getAuthPassword(),
                $this->moduleConfig->getEkp($this->storeId),
                $this->logger,
                $this->moduleConfig->isSandboxMode($this->storeId)
            );
        }

        return $this->checkoutService;
    }

    /**
     * Obtain a list of available services for the given postal code and date.
     *
     * @param string $recipientZip
     * @param \DateTime $startDate
     * @param string[] $headers
     * @return CarrierServiceInterface[]
     *
     * @throws ClientException
     * @throws ServerException
     * @throws AuthenticationException
     * @throws \Exception
     */
    public function getCarrierServices(string $recipientZip, \DateTime $startDate, array $headers = []): array
    {
        return $this->getCheckoutService()->getCarrierServices($recipientZip, $startDate, $headers);
    }
}
