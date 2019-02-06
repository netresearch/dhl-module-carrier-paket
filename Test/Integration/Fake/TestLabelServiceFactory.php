<?php
/**
 * See LICENSE.md for license details.
 */
namespace Dhl\Paket\Test\Integration\Fake;

use Dhl\Sdk\Bcs\Api\ServiceFactoryInterface;
use Dhl\Sdk\Bcs\Webservice\Soap\Service\CreateShipmentOrderServiceInterface;
use Dhl\Sdk\Bcs\Webservice\Soap\Service\ValidateShipmentService;
use Dhl\Sdk\Bcs\Webservice\Soap\SoapClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class TestLabelServiceFactory
 *
 * @package Dhl\Paket\Test
 * @copyright 2018 Netresearch DTT GmbH
 * @link      http://www.netresearch.de/
 */
class TestLabelServiceFactory implements ServiceFactoryInterface
{
    /**
     * @var CreateShipmentOrderServiceInterface
     */
    private $labelService;

    /**
     * TestLabelServiceFactory constructor.
     *
     * @param CreateShipmentOrderServiceInterface $labelService
     */
    public function __construct(CreateShipmentOrderServiceInterface $labelService)
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
     * @return CreateShipmentOrderServiceInterface
     */
    public function createShipmentOrderService(
        SoapClientInterface $soapClient,
        LoggerInterface $logger
    ): CreateShipmentOrderServiceInterface {
        return $this->labelService;
    }
}
