<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Provider\Controller\SaveShipment;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Prepare POST data as sent to the `admin/order_shipment/save` controller
 */
class PostDataProvider
{
    /**
     * Pack all order items into one package. Cross-border data is omitted.
     *
     * @param OrderInterface $order
     * @return mixed[]
     */
    public static function singlePackageDomestic(OrderInterface $order)
    {
        $package = [
            'packageId' => '1',
            'items' => [],
            'package' => [
                'packageDetails' => [
                    'productCode' => 'V01PAK',
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

        return ['packages' => [$package]];
    }

    public static function singlePackageDomesticWithCodAndWarenpostProduct(OrderInterface $order)
    {
        $codAmount = $order->getBaseShippingAmount();
        $package = [
            'packageId' => '1',
            'items' => [],
            'package' => [
                'packageDetails' => [
                    'productCode' => 'V62WP',
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
            $codAmount += $orderItem->getBasePrice();
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
        $package['service'][Codes::CHECKOUT_SERVICE_CASH_ON_DELIVERY] = [
            'enabled' => true,
            'codAmount' => $codAmount,
            'addCodFee' => null
        ];

        return ['packages' => [$package]];
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
            $packageDetails = [
                'productCode' => 'V01PAK',
                'packagingWeight' => $packagingWeight,
                'weight' => $orderItem->getWeight() * $orderItem->getQtyOrdered() + (float)$packagingWeight,
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

        return ['packages' => $packages];
    }

    /**
     * Pack each order item into an individual package and use cod service.
     *
     * @param OrderInterface $order
     * @return mixed[]
     */
    public static function multiPackageDomesticWithCod(OrderInterface $order)
    {
        $packages = [];

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
            $packageDetails = [
                'productCode' => 'V01PAK',
                'packagingWeight' => $packagingWeight,
                'weight' => $orderItem->getWeight() * $orderItem->getQtyOrdered() + (float)$packagingWeight,
                'weightUnit' => \Zend_Measure_Weight::KILOGRAM,
                'width' => '20',
                'height' => '20',
                'length' => '30',
                'sizeUnit' => \Zend_Measure_Length::CENTIMETER,
            ];

            $services = [
                Codes::CHECKOUT_SERVICE_CASH_ON_DELIVERY => [
                    'enabled' => true,
                    'codAmount' => $order->getBaseShippingAmount() + $orderItem->getBasePrice(),
                    'addCodFee' => null
                ]
            ];

            $packages[] = [
                'packageId' => $packageId,
                'items' => [
                    $orderItem->getItemId() => ['details' => $itemDetails]
                ],
                'package' => [
                    'packageDetails' => $packageDetails,
                ],
                'service' => $services
            ];

            $packageId++;
        }

        return ['packages' => $packages];
    }
}
