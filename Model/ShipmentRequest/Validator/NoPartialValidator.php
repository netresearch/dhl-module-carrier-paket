<?php
/**
 * See LICENSE.md for license details.
 */
namespace Dhl\Paket\Model\ShipmentRequest\Validator;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\RequestValidatorInterface;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class NoPartialValidator
 *
 * Validate that the complete order is shipped together if the
 * Insurance service or CoD payment method were chosen. These are
 * not compatible with multi-package or multi-shipment.
 *
 * @author Max Melzer <max.melzer@netresearch.de>
 * @link   https://www.netresearch.de/
 */
class NoPartialValidator implements RequestValidatorInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * NoPartialValidator constructor.
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
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
     * Check if an order allows partial shipment.
     *
     * Partial shipments are not allowed if
     * - the order was placed with a COD payment method or
     * - any of the packages is supposed to be booked with additional insurance service.
     *
     * @param Request $request
     * @return bool
     */
    private function canShipPartially(Request $request): bool
    {
        $packages = $request->getData('packages');

        $hasInsuranceService = false;
        foreach ($packages as $package) {
            $serviceData = $package['params']['services'][Codes::PACKAGING_SERVICE_INSURANCE] ?? [];
            $hasInsuranceService = $hasInsuranceService || ($serviceData['enabled'] ?? false);
        }

        //todo(nr): read from package params once DHLGW-736 is solved
        $payment = $request->getOrderShipment()->getOrder()->getPayment();
        $hasCodService = $payment && $this->config->isCodPaymentMethod($payment->getMethod());

        return !$hasInsuranceService && !$hasCodService;
    }

    public function validate(Request $request)
    {
        if ($this->isPartialShipment($request) && !$this->canShipPartially($request)) {
            throw new ValidatorException(
                __('Partial shipments with Cash on Delivery or Insurance service are not supported. Please ship the entire order in one package or deselect the service.')
            );
        }
    }
}
