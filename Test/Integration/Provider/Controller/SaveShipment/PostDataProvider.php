<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Provider\Controller\SaveShipment;

use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\ConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Prepare POST data as sent to the `admin/order_shipment/save` controller
 */
class PostDataProvider
{
    /**
     * @param OrderInterface|Order $order
     * @return string|null
     */
    private static function getShippingProduct(OrderInterface $order)
    {
        /** @var ConfigInterface $config */
        $config = Bootstrap::getObjectManager()->get(ConfigInterface::class);
        /** @var ShippingProducts $shippingProducts */
        $shippingProducts = Bootstrap::getObjectManager()->get(ShippingProducts::class);
        $originCountry = $config->getOriginCountry($order->getStoreId());
        $euCountries = $config->getEuCountries($order->getStoreId());

        $applicableProducts = $shippingProducts->getShippingProducts(
            $originCountry,
            $order->getShippingAddress()->getCountryId(),
            $euCountries
        );

        return array_pop($applicableProducts)[0];
    }

    /**
     * @param OrderItemInterface $orderItem
     * @param mixed[] $package
     */
    private static function addItemToPackage(OrderItemInterface $orderItem, array &$package)
    {
        $packageWeight = $package['params']['weight'] ?? 0;
        $packageValue = $package['params']['customs_value'] ?? 0;

        $package['items'][$orderItem->getItemId()] = [
            'qty' => $orderItem->getQtyOrdered(),
            'customs_value' => $orderItem->getBasePrice(),
            'price' => $orderItem->getBasePrice(),
            'name' => $orderItem->getName(),
            'weight' => $orderItem->getWeight(),
            'product_id' => $orderItem->getProductId(),
            'order_item_id' => $orderItem->getItemId(),
        ];

        // $packageWeight += $orderItem->getRowWeight();
        $packageWeight += $orderItem->getWeight() * $orderItem->getQtyOrdered();
        $packageValue += $orderItem->getRowTotalInclTax();

        $packageParams = array_merge(
            $package['params'] ?? [],
            [
                'weight' => $packageWeight,
                'customs_value' => $packageValue,
                'length' => '30.0',
                'width' => '20.0',
                'height' => '20.0',
                'weight_units' => \Zend_Measure_Weight::KILOGRAM,
                'dimension_units' => \Zend_Measure_Length::CENTIMETER,
                'content_type' => '',
                'content_type_other' => '',
            ]
        );

        $package['params'] = $packageParams;
    }

    /**
     * Pack all order items into one package.
     *
     * @param OrderInterface $order
     * @return mixed[]
     */
    public static function singlePackage(OrderInterface $order)
    {
        $shipment = [
            'items' => [],
            'create_shipping_label' => '1',
        ];

        $package = [
            'params' => [
                'container' => self::getShippingProduct($order),
            ],
            'items' => [],
        ];

        /** @var OrderItemInterface $orderItem */
        foreach ($order->getItems() as $orderItem) {
            $shipment['items'][$orderItem->getItemId()] = $orderItem->getQtyOrdered();

            self::addItemToPackage($orderItem, $package);
        }

        $postData = [
            'shipment' => $shipment,
            'packages' => ['1' => $package],
        ];

        return $postData;
    }

    /**
     * Pack each order item into an individual package.
     *
     * @param OrderInterface $order
     * @return mixed[]
     */
    public static function multiPackage(OrderInterface $order)
    {
        $shipment = [
            'items' => [],
            'create_shipping_label' => '1',
        ];

        $packages = [];
        foreach ($order->getItems() as $orderItem) {
            $shipment['items'][$orderItem->getItemId()] = $orderItem->getQtyOrdered();

            $package = [
                'params' => [
                    'container' => self::getShippingProduct($order),
                ],
                'items' => [],
            ];

            self::addItemToPackage($orderItem, $package);

            $packageId = (string) (count($packages) + 1);
            $packages[$packageId] = $package;
        }

        $postData = [
            'shipment' => $shipment,
            'packages' => $packages,
        ];

        return $postData;
    }
}
