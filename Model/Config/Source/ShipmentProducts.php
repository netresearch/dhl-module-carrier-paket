<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Config\Source;

use Dhl\Sdk\Bcs\Api\ShipmentRequestBuilderInterface;
use Dhl\ShippingCore\Model\Config\CoreConfigInterface;

/**
 * Class ShipmentProducts
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.netresearch.de/
 */
class ShipmentProducts implements \Magento\Framework\Option\ArrayInterface
{
    const DELIMITER = ';';

    /**
     * @var CoreConfigInterface
     */
    private $shippingCoreConfig;

    /**
     * ShipmentProducts constructor.
     *
     * @param CoreConfigInterface $shippingCoreConfig
     */
    public function __construct(
        CoreConfigInterface $shippingCoreConfig
    ) {
        $this->shippingCoreConfig = $shippingCoreConfig;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $originCountryCode = $this->shippingCoreConfig->getOriginCountry();
        $options           = ShipmentRequestBuilderInterface::PRODUCTS[$originCountryCode] ?? [];

        return array_map(
            function ($value) {
                return [
                    'value' => $value,
                    'label' => __($value),
                ];
            },
            $options
        );
    }
}
