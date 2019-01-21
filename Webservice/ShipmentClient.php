<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Model\Config\ModuleConfigInterface;
use Dhl\Sdk\Bcs\Api\Data\CreateShipmentOrderResponseInterface;
use Dhl\Sdk\Bcs\Api\Data\ShipmentRequestInterface;
use Dhl\Sdk\Bcs\Api\ServiceFactoryInterface;
use Dhl\Sdk\Bcs\Webservice\SoapClientFactory;
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
     * @var SoapClientFactory
     */
    private $soapClientFactory;

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
     * @param SoapClientFactory $soapClientFactory
     * @param ModuleConfigInterface $moduleConfig The module configuration instance
     * @param LoggerInterface $logger A logger instance
     */
    public function __construct(
        ServiceFactoryInterface $serviceFactory,
        SoapClientFactory $soapClientFactory,
        ModuleConfigInterface $moduleConfig,
        LoggerInterface $logger
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->soapClientFactory = $soapClientFactory;
        $this->moduleConfig = $moduleConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function performShipmentOrderRequest(ShipmentRequestInterface $request): CreateShipmentOrderResponseInterface
    {
        // Create a new soap client instance
        $soapClient = $this->soapClientFactory->create(
            $this->moduleConfig->getAuthUsername(),
            $this->moduleConfig->getAuthPassword(),
            $this->moduleConfig->getApiUsername(),
            $this->moduleConfig->getApiPassword(),
            $this->moduleConfig->sandboxModeEnabled()
        );

        // Create service instance
        $service = $this->serviceFactory->createShipmentOrderService(
            $soapClient,
            $this->logger
        );

        return $service->performRequest($request);
    }
}
