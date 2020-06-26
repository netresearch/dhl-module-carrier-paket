<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\Bcs\Api\Data\AuthenticationStorageInterfaceFactory;
use Dhl\Sdk\Paket\Bcs\Api\ServiceFactoryInterface;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Psr\Log\LoggerInterface;

class ShipmentService implements ShipmentServiceInterface
{
    /**
     * @var AuthenticationStorageInterfaceFactory
     */
    private $authStorageFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

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

    /**
     * ShipmentService constructor.
     *
     * @param AuthenticationStorageInterfaceFactory $authStorageFactory
     * @param ModuleConfig $moduleConfig
     * @param ServiceFactoryInterface $serviceFactory
     * @param LoggerInterface $logger
     * @param \int $storeId
     */
    public function __construct(
        AuthenticationStorageInterfaceFactory $authStorageFactory,
        ModuleConfig $moduleConfig,
        ServiceFactoryInterface $serviceFactory,
        LoggerInterface $logger,
        int $storeId
    ) {
        $this->authStorageFactory = $authStorageFactory;
        $this->moduleConfig = $moduleConfig;
        $this->serviceFactory = $serviceFactory;
        $this->logger = $logger;
        $this->storeId = $storeId;
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
            $authStorage = $this->authStorageFactory->create(
                [
                    'applicationId' => $this->moduleConfig->getAuthUsername($this->storeId),
                    'applicationToken' => $this->moduleConfig->getAuthPassword($this->storeId),
                    'user' => $this->moduleConfig->getUser($this->storeId),
                    'signature' => $this->moduleConfig->getSignature($this->storeId),
                ]
            );

            $this->shipmentService = $this->serviceFactory->createShipmentService(
                $authStorage,
                $this->logger,
                $this->moduleConfig->isSandboxMode($this->storeId)
            );
        }

        return $this->shipmentService;
    }

    public function createShipments(array $shipmentOrders): array
    {
        return $this->getService()->createShipments($shipmentOrders);
    }

    public function cancelShipments(array $shipmentNumbers): array
    {
        return $this->getService()->cancelShipments($shipmentNumbers);
    }
}
