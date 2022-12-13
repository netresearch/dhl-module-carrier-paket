<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\Sdk\Paket\Bcs\RequestBuilder\ShipmentOrderRequestBuilder;

/**
 * Creates a request builder for the configured shipping API (REST or SOAP)
 */
class ShipmentOrderRequestBuilderFactory
{
    /**
     * @var ModuleConfig
     */
    private $config;

    public function __construct(ModuleConfig $config)
    {
        $this->config = $config;
    }

    public function create(int $storeId): ShipmentOrderRequestBuilderInterface
    {
        if ($this->config->getShippingApiType($storeId) === ModuleConfig::SHIPPING_API_SOAP) {
            return new ShipmentOrderRequestBuilder(ShipmentOrderRequestBuilderInterface::REQUEST_TYPE_SOAP);
        } else {
            return new ShipmentOrderRequestBuilder(ShipmentOrderRequestBuilderInterface::REQUEST_TYPE_REST);
        }
    }
}
