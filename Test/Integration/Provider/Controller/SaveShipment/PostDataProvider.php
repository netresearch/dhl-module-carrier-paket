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
     * Pack all order items into one package. Cross-border data is omitted.
     *
     * @param OrderInterface $order
     * @return mixed[]
     */
    public static function singlePackageDomestic(OrderInterface $order)
    {
        $productCode = self::getShippingProduct($order);
        // $packageWeight += $orderItem->getRowWeight();

        $package = [
            'packageId' => '1',
            'items' => [],
            'package' => [
                'packageDetails' => [
                    'productCode' => $productCode,
                    'packagingWeight' => '0.33',
                    'weight' => '0',
                    'weightUnit' => \Zend_Measure_Weight::KILOGRAM,
                    'width' => '20',
                    'height' => '20',
                    'length' => '30',
                    'sizeUnit' => \Zend_Measure_Length::CENTIMETER,
                ]
            ]
        ];

        /** @var OrderItemInterface $orderItem */
        foreach ($order->getItems() as $orderItem) {
            $itemDetails = [
                'qty' => $orderItem->getQtyOrdered(),
                'qtyToShip' => $orderItem->getQtyOrdered(),
                'weight' => $orderItem->getWeight(),
                'productId' => $orderItem->getProductId(),
                'productName' => $orderItem->getName(),
                'price' => $orderItem->getBasePrice(),
            ];

            $package['items'][$orderItem->getItemId()]['details'] = $itemDetails;

            $rowWeight = $orderItem->getWeight() * $orderItem->getQtyOrdered();
            $package['package']['packageDetails']['weight'] += $rowWeight;
        }

        $package['package']['packageDetails']['weight'] += $package['package']['packageDetails']['packagingWeight'];

        return [$package];
    }

    /**
     * Pack each order item into an individual package. Cross-border data is omitted.
     *
     * @param OrderInterface $order
     * @return mixed[]
     */
    public static function multiPackageDomestic(OrderInterface $order)
    {
        $packages = [];
        $productCode = self::getShippingProduct($order);

        $packageId = 1;
        foreach ($order->getItems() as $orderItem) {
            $itemDetails = [
                'qty' => $orderItem->getQtyOrdered(),
                'qtyToShip' => $orderItem->getQtyOrdered(),
                'weight' => $orderItem->getWeight(),
                'productId' => $orderItem->getProductId(),
                'productName' => $orderItem->getName(),
                'price' => $orderItem->getBasePrice(),
            ];

            $packagingWeight = '0.33';
            $packageDetails =  [
                'productCode' => $productCode,
                'packagingWeight' => $packagingWeight,
                'weight' => $orderItem->getWeight() * $orderItem->getQtyOrdered() + (float) $packagingWeight,
                'weightUnit' => \Zend_Measure_Weight::KILOGRAM,
                'width' => '20',
                'height' => '20',
                'length' => '30',
                'sizeUnit' => \Zend_Measure_Length::CENTIMETER,
            ];

            $packages[] = [
                'packageId' => $packageId,
                'items' => [
                    $orderItem->getItemId() => ['details' => $itemDetails]
                ],
                'package' => [
                    'packageDetails' => $packageDetails,
                ]
            ];

            $packageId++;
        }

        return $packages;
    }
}
