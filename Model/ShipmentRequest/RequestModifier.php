<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\Paket\Model\Carrier\PaketFactory;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\PackagingOptionReaderInterfaceFactory;
use Dhl\ShippingCore\Api\RequestModifierInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class RequestModifier
 * @package Dhl\Paket\Model\ShipmentRequest
 */
class RequestModifier implements RequestModifierInterface
{
    /**
     * @var ConfigInterface
     */
    private $dhlConfig;

    /**
     * @var RequestModifierInterface
     */
    private $coreModifier;

    /**
     * @var PackagingOptionReaderInterfaceFactory
     */
    private $packagingOptionReaderFactory;

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
     *
     * @param ConfigInterface $dhlConfig
     * @param RequestModifierInterface $coreModifier
     * @param PackagingOptionReaderInterfaceFactory $packagingOptionReaderFactory
     * @param PaketFactory $carrierFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        ConfigInterface $dhlConfig,
        RequestModifierInterface $coreModifier,
        PackagingOptionReaderInterfaceFactory $packagingOptionReaderFactory,
        PaketFactory $carrierFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->dhlConfig = $dhlConfig;
        $this->coreModifier = $coreModifier;
        $this->packagingOptionReaderFactory = $packagingOptionReaderFactory;
        $this->carrierFactory = $carrierFactory;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Determine product used for the current route.
     *
     * @fixme(nr): introduce separate service class, do not instantiate carrier model,
     *
     * @param string $origin Shipper country code
     * @param string $destination Recipient country code
     * @return string mixed
     */
    private function getProductForRoute($origin, $destination)
    {
        $params = $this->dataObjectFactory->create(
            [
                'data' => [
                    'country_shipper' => $origin,
                    'country_recipient' => $destination
                ]
            ]
        );

        $carrier = $this->carrierFactory->create();
        $productCode = current(array_keys($carrier->getContainerTypes($params)));

        return $productCode;
    }

    /**
     * Add default shipping product, e.g. V01PAK or V53PAK
     *
     * @param Request $shipmentRequest
     */
    private function modifyPackage(Request $shipmentRequest)
    {
        $productCode = $this->getProductForRoute(
            $shipmentRequest->getShipperAddressCountryCode(),
            $shipmentRequest->getRecipientAddressCountryCode()
        );

        $packageId = $shipmentRequest->getData('package_id');
        $package = $shipmentRequest->getData('packages')[$packageId];
        $package['params']['container'] = $productCode;

        $shipmentRequest->setData('packages', [$packageId => $package]);
        $shipmentRequest->setData('package_params', $package['params']);
    }

    /**
     * Add service selection to shipment request.
     *
     * @todo(nr): where to add service data within the shipment request is to be defined.
     *
     * @param Request $shipmentRequest
     * @throws LocalizedException
     */
    private function modifyServices(Request $shipmentRequest)
    {
        $shipment = $shipmentRequest->getOrderShipment();

        /** @var \Dhl\ShippingCore\Api\PackagingOptionReaderInterface $packagingOptionReader */
        $packagingOptionReader = $this->packagingOptionReaderFactory->create(['shipment' => $shipment]);
        $packagingOptionReader->getServiceOptionValue('printOnlyIfCodeable', 'enabled');
    }

    /**
     * Add customs data to package params and package items.
     *
     * @param Request $shipmentRequest
     * @throws LocalizedException
     */
    private function modifyCustoms(Request $shipmentRequest)
    {
        $recipientCountry = $shipmentRequest->getRecipientAddressCountryCode();
        $euCountries = $this->dhlConfig->getEuCountries($shipmentRequest->getOrderShipment()->getStoreId());

        if (in_array($recipientCountry, $euCountries, true)) {
            // route within EU, no customs data to add.
            return;
        }

        $shipment = $shipmentRequest->getOrderShipment();

        /** @var \Dhl\ShippingCore\Api\PackagingOptionReaderInterface $reader */
        $reader = $this->packagingOptionReaderFactory->create(['shipment' => $shipment]);

        $packageId = $shipmentRequest->getData('package_id');
        $package = $shipmentRequest->getData('packages')[$packageId];
        $package['params']['content_type'] = $reader->getPackageOptionValue('packageCustoms', 'contentType');
        $package['params']['content_type_other'] = $reader->getPackageOptionValue('packageCustoms', 'explanation');
        $package['params']['customs_value'] = $reader->getPackageOptionValue('packageCustoms', 'customsValue');
        $package['params']['customs'] = [
            'exportDescription' => $reader->getPackageOptionValue('packageCustoms', 'exportDescription'),
            'termsOfTrade' => $reader->getPackageOptionValue('packageCustoms', 'termsOfTrade'),
        ];

        foreach ($package['items'] as $orderItemId => &$packageItem) {
            $packageItem['customs_value'] = $reader->getItemOptionValue($orderItemId, 'itemCustoms', 'customsValue');
            $packageItem['customs'] = [
                'exportDescription' => $reader->getItemOptionValue($orderItemId, 'itemCustoms', 'exportDescription'),
                'hsCode' => $reader->getItemOptionValue($orderItemId, 'itemCustoms', 'hsCode'),
                'countryOfOrigin' => $reader->getItemOptionValue($orderItemId, 'itemCustoms', 'countryOfOrigin'),
            ];
        }

        $shipmentRequest->setData('packages', [$packageId => $package]);
        $shipmentRequest->setData('package_items', $package['items']);
        $shipmentRequest->setData('package_params', $package['params']);
    }

    /**
     * Add shipment request data using given shipment.
     *
     * The request modifier collects all additional data from defaults (config, product attributes)
     * during bulk label creation where no user input (packaging popup) is involved.
     *
     * @param Request $shipmentRequest
     * @throws LocalizedException
     */
    public function modify(Request $shipmentRequest)
    {
        // add carrier-agnostic data
        $this->coreModifier->modify($shipmentRequest);

        // add carrier-specific data
        $this->modifyPackage($shipmentRequest);
        $this->modifyServices($shipmentRequest);
        $this->modifyCustoms($shipmentRequest);
    }
}
