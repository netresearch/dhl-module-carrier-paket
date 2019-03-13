<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\Bcs\Api\Data\AuthenticationStorageInterface;
use Dhl\Sdk\Paket\Bcs\Api\Data\AuthenticationStorageInterfaceFactory;
use Dhl\Sdk\Paket\Bcs\Api\ServiceFactoryInterface;
use Dhl\Sdk\Paket\Bcs\Model\CreateShipment\RequestType\ShipmentOrderType;
use JsonSerializable;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class ShipmentClient implements ShipmentClientInterface
{

    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    /**
     * @var AuthenticationStorageInterfaceFactory
     */
    private $authStorageFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ShipmentClient constructor.
     * @param ServiceFactoryInterface $serviceFactory
     * @param AuthenticationStorageInterfaceFactory $authStorageFactory
     * @param ModuleConfig $moduleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServiceFactoryInterface $serviceFactory,
        AuthenticationStorageInterfaceFactory $authStorageFactory,
        ModuleConfig $moduleConfig,
        LoggerInterface $logger
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->authStorageFactory = $authStorageFactory;
        $this->moduleConfig = $moduleConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function performShipmentOrderRequest(
        ShipmentOrderType $shipmentOrder,
        StoreInterface $store
    ): array {
        $storeCode = $store->getCode();

        $authStorage = $this->authStorageFactory->create([
            'applicationId'    => $this->moduleConfig->getAuthUsername($storeCode),
            'applicationToken' => $this->moduleConfig->getAuthPassword($storeCode),
            'user'             => $this->moduleConfig->getApiUsername($storeCode),
            'signature'        => $this->moduleConfig->getApiPassword($storeCode),
            'ekp'              => $this->moduleConfig->getAccountNumber($storeCode)
        ]);

        // Create service instance
        $service = $this->serviceFactory->createShipmentService(
            $authStorage,
            $this->logger,
            $this->moduleConfig->isSandboxMode($storeCode)
        );

        return $service->createShipments([ $shipmentOrder ]);
    }
}
