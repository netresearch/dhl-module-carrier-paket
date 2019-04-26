<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Fixture\Data;

use Dhl\ShippingCore\Model\Attribute\Backend\ExportDescription;
use Dhl\ShippingCore\Model\Attribute\Backend\TariffNumber;
use Dhl\ShippingCore\Model\Attribute\Source\DGCategory;
use Magento\Catalog\Model\Product\Type;

/**
 * Class SimpleProduct
 *
 * @package Dhl\Test\Integration\Fixture
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class SimpleProduct implements ProductInterface
{
    public function getType(): string
    {
        return Type::TYPE_SIMPLE;
    }

    public function getSku(): string
    {
        return 'DHL-01';
    }

    public function getPrice(): float
    {
        return 24.99;
    }

    public function getWeight(): float
    {
        return 2.4;
    }

    public function getCustomAttributes(): array
    {
        return [
            DGCategory::CODE => null,
            ExportDescription::CODE => 'Export description of a simple product.',
            TariffNumber::CODE => '12345678',
        ];
    }

    public function getCheckoutQty(): int
    {
        return 2;
    }
}
