<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\Paket\Model\Carrier\PaketFactory;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\PackagingOptionReaderInterfaceFactory;
use Dhl\ShippingCore\Api\RequestModifierInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class RequestModifier
 *
 * @package Dhl\Paket\Model\ShipmentRequest
 */
class RequestModifier implements RequestModifierInterface
{
    /**
     * @var RequestModifierInterface
     */
    private $coreModifier;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var ConfigInterface
     */
    private $dhlConfig;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

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
     * @param RequestModifierInterface $coreModifier
     * @param ModuleConfig $config
     * @param ConfigInterface $dhlConfig
     * @param ShippingProducts $shippingProducts
     * @param PackagingOptionReaderInterfaceFactory $packagingOptionReaderFactory
     * @param PaketFactory $carrierFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        RequestModifierInterface $coreModifier,
        ModuleConfig $config,
        ConfigInterface $dhlConfig,
        ShippingProducts $shippingProducts,
        PackagingOptionReaderInterfaceFactory $packagingOptionReaderFactory,
        PaketFactory $carrierFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->config = $config;
        $this->dhlConfig = $dhlConfig;
        $this->coreModifier = $coreModifier;
        $this->shippingProducts = $shippingProducts;
        $this->packagingOptionReaderFactory = $packagingOptionReaderFactory;
        $this->carrierFactory = $carrierFactory;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Add default shipping product, e.g. V01PAK or V53PAK
     *
     * @param Request $shipmentRequest
     * @throws LocalizedException
     */
    private function modifyPackage(Request $shipmentRequest)
    {
        $originCountry = $shipmentRequest->getShipperAddressCountryCode();
        $destinationCountry = $shipmentRequest->getRecipientAddressCountryCode();

        // load applicable products for the current route
        $euCountries = $this->dhlConfig->getEuCountries($shipmentRequest->getOrderShipment()->getStoreId());
        $applicableProducts = $this->shippingProducts->getShippingProducts(
            $originCountry,
            $destinationCountry,
            $euCountries
        );

        // check if defaults applicable to the current route are configured
        $defaults = array_intersect_key(
            $this->config->getShippingProductDefaults($shipmentRequest->getOrderShipment()->getStoreId()),
            $applicableProducts
        );

        $defaultProduct = current($defaults);
        $applicableProductCodes = current($applicableProducts);
        if (!in_array($defaultProduct, $applicableProductCodes)) {
            $message = __('The product %1 is not valid for the route %2-%3.', $defaultProduct, $originCountry, $destinationCountry);
            throw new LocalizedException($message);
        }

        $paketPackages = [];
        foreach ($shipmentRequest->getData('packages') as $packageId => $package) {
            $package['params']['shipping_product'] = $defaultProduct;
            $paketPackages[$packageId] = $package;
        }

        $shipmentRequest->setData('packages', $paketPackages);
        $shipmentRequest->setData('package_params', $paketPackages[$shipmentRequest->getData('package_id')]['params']);
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
        $this->modifyCustoms($shipmentRequest);
    }
}
