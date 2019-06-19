<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Generator;

use Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Test\Integration\Generator\ShipmentRequestData as ShippingCoreGenerator;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

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
     * @param Order $order
     * @return mixed
     */
    private static function getContainerType(Order $order): string
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ConfigInterface $config */
        $config = $objectManager->get(ConfigInterface::class);
        /** @var ShippingProductsInterface $shippingProducts */
        $shippingProducts = $objectManager->get(ShippingProductsInterface::class);
        $originCountry = $config->getOriginCountry($order->getStoreId());
        $euCountries = $config->getEuCountries($order->getStoreId());
        $container = current(
            $shippingProducts->getApplicableCodes(
                $originCountry,
                $order->getShippingAddress()->getCountryId(),
                $euCountries
            )
        );

        return $container;
    }
}
