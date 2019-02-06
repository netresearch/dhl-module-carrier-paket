<?php
/**
 * See LICENSE.md for license details.
 */
namespace Dhl\Paket\Test\Integration\Fake;

use Dhl\Sdk\Bcs\Webservice\Soap\SoapClientInterface;
use Dhl\Sdk\Bcs\Webservice\SoapClientFactory;

/**
 * Class TestSoapClientFactory
 *
 * @package Dhl\Paket\Test
 * @copyright 2018 Netresearch DTT GmbH
 * @link      http://www.netresearch.de/
 */
class TestSoapClientFactory extends SoapClientFactory
{
    /**
     * @var SoapClientInterface
     */
    private $soapClient;

    /**
     * TestSoapClientFactory constructor.
     * @param SoapClientInterface $soapClient
     */
    public function __construct(SoapClientInterface $soapClient)
    {
        $this->soapClient = $soapClient;
    }

    /**
     * Creates a new soap client instance.
     *
     * @param string $authUsername The SOAP header authentification username
     * @param string $authPassword The SOAP header authentification password
     * @param string $apiUsername The API access username
     * @param string $apiPassword The API access password
     * @param bool $useSandbox Whether to use sandbox mode or not
     *
     * @return SoapClientInterface
     */
    public function create(
        string $authUsername,
        string $authPassword,
        string $apiUsername,
        string $apiPassword,
        bool $useSandbox = true
    ): SoapClientInterface {
        return $this->soapClient;
    }
}
