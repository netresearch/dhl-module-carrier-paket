<?php
/**
 * See LICENSE.md for license details.
 */
namespace Dhl\Paket\Model\Packaging;

use Dhl\Paket\Model\ProcessorInterface;
use Dhl\ShippingCore\Api\ConfigInterface;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class ShipmentRequestValidator
 *
 * @package Dhl\Paket\Model\Packaging
 * @author Max Melzer <max.melzer@netresearch.de>
 */
class ShipmentRequestValidator
{
    /**
     * @var ConfigInterface
     */
    private $shippingCoreConfig;

    /**
     * ShipmentRequestValidator constructor.
     *
     * @param ConfigInterface $shippingCoreConfig
     */
    public function __construct(ConfigInterface $shippingCoreConfig)
    {
        $this->shippingCoreConfig = $shippingCoreConfig;
    }

    /**
     * Collect quantities of all the order's shippable items and compare with items included in the current shipment.
     *
     * @param Request $request
     * @return bool
     */
    private function isPartialShipment(Request $request): bool
    {
        $itemQtyOrdered = array_map(function (OrderItemInterface $item) {
            if ($item->getIsVirtual()) {
                // virtual items are not shipped, ignore.
                return 0;
            }

            if ($item->getParentItem() && $item->getParentItem()->getProductType() === Configurable::TYPE_CODE) {
                // children of a configurable are not shipped, ignore.
                return 0;
            }

            if ($item->getParentItem() && $item->getParentItem()->getProductType() === Type::TYPE_CODE) {
                $parentOrderItem = $item->getParentItem();
                $shipmentType = (int) $parentOrderItem->getProductOptionByCode('shipment_type');
                if ($shipmentType === AbstractType::SHIPMENT_TOGETHER) {
                    // children of a bundle (shipped together) are not shipped, ignore.
                    return 0;
                }
            }

            if ($item->getProductType() === Type::TYPE_CODE) {
                $shipmentType = (int) $item->getProductOptionByCode('shipment_type');
                if ($shipmentType === AbstractType::SHIPMENT_SEPARATELY) {
                    // a bundle with children (shipped separately) is not shipped, ignore.
                    return 0;
                }
            }

            return $item->getQtyOrdered();
        }, $request->getOrderShipment()->getOrder()->getAllItems());

        $qtyOrdered = array_sum($itemQtyOrdered);
        $qtyShipped = (float)$request->getOrderShipment()->getTotalQty();

        return ($qtyOrdered !== $qtyShipped) || (count($request->getData('packages')) > 1);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function hasCompleteShipmentOnlyServices(Request $request): bool
    {
        $payment = $request->getOrderShipment()->getOrder()->getPayment();
        $hasCodService = $payment && $this->shippingCoreConfig->isCodPaymentMethod($payment->getMethod());
        $hasInsuranceService = $request
                ->getData('package_params')
                ->getServices()[ProcessorInterface::PACKAGING_SERVICE_INSURANCE]['enabled'] ?? false;

        return $hasCodService || $hasInsuranceService;
    }

    /**
     * The Insurance service and CoD payment method are not compatible with multi-package or multi-shipment.
     * Throws an Exception if this rule is violated.
     *
     * @param Request $request
     * @return void
     *
     * @throws LocalizedException
     */
    public function validate(Request $request)
    {
        if ($this->isPartialShipment($request) && $this->hasCompleteShipmentOnlyServices($request)) {
            throw new LocalizedException(
                __('Partial shipments with Cash on Delivery or Insurance service are not supported. Please ship the entire order.')
            );
        }
    }
}
