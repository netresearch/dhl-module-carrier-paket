<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Webservice;

use Dhl\Sdk\ParcelDe\Shipping\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\Sdk\ParcelDe\Shipping\RequestBuilder\ShipmentOrderRequestBuilder;

/**
 * Creates a request builder for the configured shipping API (REST)
 */
class ShipmentOrderRequestBuilderFactory
{

    public function create(int $storeId): ShipmentOrderRequestBuilderInterface
    {
        return new ShipmentOrderRequestBuilder();
    }
}
