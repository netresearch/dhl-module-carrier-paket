<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\Bcs\Api\Data\AuthenticationStorageInterfaceFactory;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Api\ServiceFactoryInterface;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;
use Dhl\Sdk\Paket\Bcs\Exception\AuthenticationException;
use Dhl\Sdk\Paket\Bcs\Exception\ClientException;
use Dhl\Sdk\Paket\Bcs\Exception\ServerException;
use Psr\Log\LoggerInterface;

/**
 * Class ShipmentService
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
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
     */
    private function getService()
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

    /**
     * CreateShipmentOrder is the operation call used to generate shipments with the relevant DHL Paket labels.
     *
     * @param \stdClass[] $shipmentOrders
     * @return ShipmentInterface[]
     * @throws AuthenticationException
     * @throws ClientException
     * @throws ServerException
     */
    public function createShipments(array $shipmentOrders): array
    {
        return $this->getService()->createShipments($shipmentOrders);
    }

    /**
     * This operation cancels earlier created shipments.
     *
     * @param string[] $shipmentNumbers
     * @return string[]
     * @throws AuthenticationException
     * @throws ClientException
     * @throws ServerException
     */
    public function cancelShipments(array $shipmentNumbers): array
    {
        return $this->getService()->cancelShipments($shipmentNumbers);
    }
}