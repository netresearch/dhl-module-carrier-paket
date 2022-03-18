<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Shipping;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Util\ShippingProducts;
use Netresearch\ShippingCore\Api\Shipping\ProductNameProviderInterface;

class ProductNameProvider implements ProductNameProviderInterface
{
    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

    public function __construct(ShippingProducts $shippingProducts)
    {
        $this->shippingProducts = $shippingProducts;
    }

    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }

    public function getName(string $productCode): string
    {
        $productName = $this->shippingProducts->getProductName($productCode);
        if ($productName === $productCode) {
            // nothing useful found.
            return '';
        }

        return $productName;
    }
}
