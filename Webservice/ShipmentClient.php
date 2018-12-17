<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Sdk\Bcs\Api\Data\ShipmentRequestInterface;
use Dhl\Sdk\Bcs\Api\Data\CreateShipmentOrderResponseInterface;
use Dhl\Paket\Model\Config\ModuleConfigInterface;
use Dhl\Sdk\Bcs\Api\ServiceFactoryInterface;
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
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ServiceFactoryInterface $serviceFactory The service factory instance
     * @param ModuleConfigInterface   $moduleConfig   The module configuration instance
     * @param LoggerInterface         $logger         A logger instance
     */
    public function __construct(
        ServiceFactoryInterface $serviceFactory,
        ModuleConfigInterface $moduleConfig,
        LoggerInterface $logger
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->moduleConfig   = $moduleConfig;
        $this->logger         = $logger;
    }

    /**
     * @inheritDoc
     */
    public function performShipmentOrderRequest(ShipmentRequestInterface $request): CreateShipmentOrderResponseInterface
    {
        // Create factory instance
        $soapClientFactory = new \Dhl\Sdk\Bcs\Webservice\SoapClientFactory();

        // Create a new soap client instance
        $soapClient = $soapClientFactory->create(
            $this->moduleConfig->getAuthUsername(),
            $this->moduleConfig->getAuthPassword(),
            $this->moduleConfig->getApiUsername(),
            $this->moduleConfig->getApiPassword(),
            $this->moduleConfig->getLocation()
        );

        // Create service instance
        $service = $this->serviceFactory->createShipmentOrderService(
            $soapClient,
            $this->logger
        );

        return $service->performRequest($request);
    }
}
