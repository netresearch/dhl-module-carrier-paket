<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Fake;

use Dhl\Sdk\Bcs\Api\Data\CreateShipmentOrderResponseInterface;
use Dhl\Sdk\Bcs\Api\Data\ShipmentRequestInterface;
use Dhl\Sdk\Bcs\Webservice\Exception\AuthenticationException;
use Dhl\Sdk\Bcs\Webservice\Exception\GeneralErrorException;
use Dhl\Sdk\Bcs\Webservice\Exception\HardValidationException;
use Dhl\Sdk\Bcs\Webservice\Soap\Service\CreateShipmentOrderServiceInterface;

/**
 * Class CreateShipmentOrderService
 *
 * @package Dhl\Paket\Test\Integration\Fake
 * @author    Max Melzer <max.melzer@netresearch.de>
 * @copyright 2019 Netresearch DTT GmbH
 * @link      http://www.netresearch.de/
 */
class CreateShipmentOrderService implements CreateShipmentOrderServiceInterface
{
    /**
     * @var CreateShipmentOrderResponseInterface
     */
    private $response;

    /**
     * @var ShipmentRequestInterface
     */
    private $lastRequest;

    /**
     * @var AuthenticationException|GeneralErrorException|HardValidationException|null
     */
    private $expectedException;

    /**
     * @param ShipmentRequestInterface $request
     * @return CreateShipmentOrderResponseInterface
     * @throws \Throwable
     */
    public function performRequest(ShipmentRequestInterface $request): CreateShipmentOrderResponseInterface
    {
        $this->lastRequest = $request;
        if ($this->expectedException instanceof \Throwable) {
            throw $this->expectedException;
        }

        return $this->response;
    }

    /**
     * @param string $message
     */
    public function setExpectAuthenticationException($message = 'Authentication exception')
    {
        $this->expectedException = new AuthenticationException($message);
    }

    /**
     * @param string $message
     */
    public function setExpectGeneralErrorException($message = 'General error exception')
    {
        $this->expectedException = new GeneralErrorException($message);
    }

    /**
     * @param string $message
     */
    public function setExpectHardValidationException($message = 'General error exception')
    {
        $this->expectedException = new HardValidationException(
            $message,
            1101,
            [$message]
        );
    }

    public function unsetExpectedException()
    {
        $this->expectedException = null;
    }

    /**
     * @param CreateShipmentOrderResponseInterface $response
     */
    public function setResponse(CreateShipmentOrderResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return CreateShipmentOrderResponseInterface
     */
    public function getResponse(): CreateShipmentOrderResponseInterface
    {
        return $this->response;
    }

    /**
     * @return ShipmentRequestInterface
     */
    public function getLastRequest(): ShipmentRequestInterface
    {
        return $this->lastRequest;
    }
}
