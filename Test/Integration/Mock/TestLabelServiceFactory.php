<?php


namespace Dhl\Paket\Test\Integration\Mock;

use Dhl\Sdk\Bcs\Api\ServiceFactoryInterface;
use Dhl\Sdk\Bcs\Webservice\Soap\Service\CreateShipmentOrderService;
use Dhl\Sdk\Bcs\Webservice\Soap\Service\ValidateShipmentService;
use Dhl\Sdk\Bcs\Webservice\Soap\SoapClientInterface;
use Psr\Log\LoggerInterface;

class TestLabelServiceFactory implements ServiceFactoryInterface
{
    /**
     * @var CreateShipmentOrderService
     */
    private $labelService;

    /**
     * TestLabelServiceFactory constructor.
     * @param CreateShipmentOrderService $labelService
     */
    public function __construct(CreateShipmentOrderService $labelService)
    {
        $this->labelService = $labelService;
    }

    /**
     * With this operation the data for a shipment can be validated before a shipment label and
     * tracking number will be created.
     *
     * @param SoapClientInterface $soapClient The soap client instance
     * @param LoggerInterface|null $logger The logger instance
     *
     * @return ValidateShipmentService
     */
    public function validateShipmentService(SoapClientInterface $soapClient, LoggerInterface $logger = null): ValidateShipmentService
    {
        // TODO: Implement validateShipmentService() method.
    }

    /**
     * CreateShipmentOrder is the operation call used to generate shipments with the relevant DHL Paket labels.
     *
     * @param SoapClientInterface $soapClient The soap client instance
     * @param LoggerInterface|null $logger The logger instance
     *
     * @return CreateShipmentOrderService
     */
    public function createShipmentOrderService(
        SoapClientInterface $soapClient,
        LoggerInterface $logger
    ): CreateShipmentOrderService {
        return $this->labelService;
    }
}
