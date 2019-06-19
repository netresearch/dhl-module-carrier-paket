<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\Paket\Model\Carrier\PaketFactory;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\RequestModifierInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class RequestModifier
 * @package Dhl\Paket\Model\ShipmentRequest
 */
class RequestModifier implements RequestModifierInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var RequestModifierInterface
     */
    private $coreModifier;

    /**
     * @var PaketFactory
     */
    private $carrierFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * RequestModifier constructor.
     * @param ModuleConfig $moduleConfig
     * @param RequestModifierInterface $coreModifier
     * @param PaketFactory $carrierFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        RequestModifierInterface $coreModifier,
        PaketFactory $carrierFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->coreModifier = $coreModifier;
        $this->carrierFactory = $carrierFactory;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @param Request $shipmentRequest
     */
    private function modifyPackageData(Request $shipmentRequest)
    {
        $orderShipment = $shipmentRequest->getOrderShipment();
        $package = $orderShipment->getPackages()[1];
        $shipperCountry = $shipmentRequest->getShipperAddressCountryCode();
        $destCountry = $shipmentRequest->getRecipientAddressCountryCode();
        $services = $this->getServiceSelection($shipmentRequest);

        $params = $this->dataObjectFactory->create(
            [
                'data' => [
                    'country_shipper' => $shipperCountry,
                    'country_recipient' => $destCountry
                ]
            ]
        );

        //fixme(nr): do not create the carrier. the called methods should not be in the carrier anyway (see comments there).
        $carrier = $this->carrierFactory->create();
        $container = current(array_keys($carrier->getContainerTypes($params)));
        $package['params']['container'] = $container;
        $package['params']['services'] = $services;

        $packages = [1 => $package];
        $shipmentRequest->setData('packages', $packages);
        $shipmentRequest->getOrderShipment()->setPackages($packages);
    }

    /**
     * @param Request $shipmentRequest
     *
     * @return Request
     */
    public function modify(Request $shipmentRequest): Request
    {
        $this->coreModifier->modify($shipmentRequest);
        $this->modifyPackageData($shipmentRequest);

        return $shipmentRequest;
    }

    /**
     * @param Request $shipmentRequest
     * @return array
     */
    private function getServiceSelection(Request $shipmentRequest): array
    {
        //TODO: implementation get checkout services
        $services = [];
        $storeId = $shipmentRequest->getOrderShipment()->getStoreId();
        $printOnlyIfCodable = $this->moduleConfig->printOnlyIfCodeable($storeId);

        $services['printOnlyIfCodeable'] = $printOnlyIfCodable;

        return $services;
    }
}
