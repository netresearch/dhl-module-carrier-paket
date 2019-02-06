<?php
/**
 * See LICENSE.md for license details.
 */
namespace Dhl\Paket\Test\Integration\Provider;

use Dhl\Sdk\Bcs\Api\ShippingProductsInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class MagentoShipmentRequestProvider
 *
 * @package Dhl\Bcs\Test\Integration\Provider
 * @author    Max Melzer <max.melzer@netresearch.de>
 * @copyright 2019 Netresearch DTT GmbH
 * @link      http://www.netresearch.de/
 */
class MagentoShipmentRequestProvider
{
    /**
     * @return Request
     */
    public static function getRequest(): Request
    {
        $packageId = 0;
        $orderId = 1;

        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        /** @var Order  $order */
        $order = $objectManager->create(Order::class, [
            'data' => [
                'id' => $orderId,
                'shipping_method' => new DataObject(['carrier_code' => 'dhlpaket']),
            ]
        ]);

        /** @var Shipment $shipment */
        $shipment = new DataObject(['order' => $order]);

        $package = [
            'params' => [
                'container' => ShippingProductsInterface::CODE_NATIONAL,
                'weight' => 42
            ],
            'items' => [],
        ];

        $request = new Request();
        $request->setData('packages', [$packageId => $package]);
        $request->setOrderShipment($shipment);
        $request->setShipperContactPersonName('Hans Mueller');
        $request->setShipperContactPersonFirstName('Hans');
        $request->setShipperContactPersonLastName('Mueller');
        $request->setShipperAddressStreet('MusterStreet 12');
        $request->setShipperAddressStreet1('MusterStreet 12');
        $request->setShipperAddressCity('Berlin');
        $request->setShipperAddressPostalCode('01234');
        $request->setShipperAddressCountryCode('DE');

        $request->setRecipientContactPersonName('Elfriede Bloed');
        $request->setRecipientContactPersonFirstName('Elfriede');
        $request->setRecipientContactPersonLastName('Bloed');
        $request->setRecipientAddressStreet('Nonnenstraße 11d');
        $request->setRecipientAddressStreet1('Nonnenstraße 11d');
        $request->setRecipientAddressCity('Leipzig');
        $request->setRecipientAddressPostalCode('04229');
        $request->setRecipientAddressCountryCode('DE');

        return $request;
    }
}
