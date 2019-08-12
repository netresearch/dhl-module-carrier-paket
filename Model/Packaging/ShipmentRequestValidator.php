<?php
/**
 * See LICENSE.md for license details.
 */
namespace Dhl\Paket\Model\Packaging;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ProcessorInterface;
use Dhl\ShippingCore\Api\ConfigInterface;
use Magento\Framework\Exception\LocalizedException;
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
                __('Partial Shipments with Cash on Delivery or the Insurance service are not supported. Please create one complete Shipment')
            );
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isPartialShipment(Request $request): bool
    {
        $qtyOrdered = (float)$request->getOrderShipment()->getOrder()->getTotalQtyOrdered();
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
}
