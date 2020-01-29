<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Generator;

use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Test\Integration\Generator\ShipmentRequestData as ShippingCoreGenerator;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class ShipmentRequestData
 * @deprecated
 * @see \Dhl\Paket\Test\Integration\Provider\Controller\SaveShipment\PostDataProvider
 */
class ShipmentRequestData
{
    /**
     * Generate a POST request array to use for shipment save controller, enhanced with DHL Paket specific data
     *
     * @param Order $order
     * @return array
     */
    public static function generatePostData(Order $order): array
    {
        $postData = ShippingCoreGenerator::generatePostData($order);
        $container = self::getContainerType($order);

        foreach ($postData['packages'] as $key => $package) {
            $postData['packages'][$key] = array_merge_recursive($package, ['params' => ['container' => $container]]);
        }

        return $postData;
    }

    /**
     * fixme(nr): this is not about containers but shipping products.
     *
     * @param Order $order
     * @return mixed
     */
    private static function getContainerType(Order $order): string
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ConfigInterface $config */
        $config = $objectManager->get(ConfigInterface::class);
        /** @var ShippingProducts $shippingProducts */
        $shippingProducts = $objectManager->get(ShippingProducts::class);
        $originCountry = $config->getOriginCountry($order->getStoreId());
        $euCountries = $config->getEuCountries($order->getStoreId());

        $applicableProducts = $shippingProducts->getShippingProducts(
            $originCountry,
            $order->getShippingAddress()->getCountryId(),
            $euCountries
        );

        $destinationRegionProducts = current($applicableProducts);

        return $destinationRegionProducts[0];
    }
}
