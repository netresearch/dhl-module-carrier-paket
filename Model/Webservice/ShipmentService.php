<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\ParcelDe\Shipping\Api\Data\OrderConfigurationInterface;
use Dhl\Sdk\ParcelDe\Shipping\Api\ShipmentServiceInterface;
use Dhl\Sdk\ParcelDe\Shipping\Auth\AuthenticationStorage;
use Dhl\Sdk\ParcelDe\Shipping\Exception\ServiceException;
use Dhl\Sdk\ParcelDe\Shipping\Service\ServiceFactory;
use Psr\Log\LoggerInterface;

class ShipmentService implements ShipmentServiceInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var ShipmentServiceInterface|null
     */
    private $shipmentService;

    public function __construct(
        ModuleConfig $moduleConfig,
        LoggerInterface $logger,
        int $storeId
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->logger = $logger;
        $this->storeId = $storeId;
    }

    /**
     * Create service instance to connect to the DHL REST API.
     *
     * @throws ServiceException
     */
    private function createRestService(): ShipmentServiceInterface
    {
        $sandboxMode = $this->moduleConfig->isSandboxMode($this->storeId);
        $appToken = 'pJDOxtJt03guK5eXKYcZt9Ez1bPi2Xvm';

        if ($sandboxMode) {
            $user = '3333333333_01';
            $pass = 'pass';
        } else {
            $user = $this->moduleConfig->getUser($this->storeId);
            $pass = $this->moduleConfig->getSignature($this->storeId);
        }

        $authStorage = new AuthenticationStorage($appToken, $user, $pass);
        $serviceFactory = new ServiceFactory(
            "dhl-module-carrier-paket/{$this->moduleConfig->getModuleVersion()}"
        );

        return $serviceFactory->createShipmentService($authStorage, $this->logger, $sandboxMode);
    }

    /**
     * Create shipment service.
     *
     * @return ShipmentServiceInterface
     * @throws ServiceException
     */
    private function getService(): ShipmentServiceInterface
    {
        if ($this->shipmentService === null) {
             $this->shipmentService = $this->createRestService();
        }

        return $this->shipmentService;
    }

    public function getVersion(): string
    {
        return $this->getService()->getVersion();
    }

    public function validateShipments(array $shipmentOrders, OrderConfigurationInterface $configuration = null): array
    {
        return $this->getService()->validateShipments($shipmentOrders, $configuration);
    }

    public function createShipments(array $shipmentOrders, OrderConfigurationInterface $configuration = null): array
    {
        return $this->getService()->createShipments($shipmentOrders, $configuration);
    }

    public function cancelShipments(
        array $shipmentNumbers,
        string $profile = OrderConfigurationInterface::DEFAULT_PROFILE
    ): array {
        return $this->getService()->cancelShipments($shipmentNumbers, $profile);
    }
}
