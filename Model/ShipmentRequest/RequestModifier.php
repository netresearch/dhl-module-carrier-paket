<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\PackagingOptionReaderInterface;
use Dhl\ShippingCore\Api\PackagingOptionReaderInterfaceFactory;
use Dhl\ShippingCore\Api\RequestModifierInterface;
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
     * RequestModifier constructor.
     *
     * @param RequestModifierInterface $coreModifier
     * @param ModuleConfig $config
     * @param ConfigInterface $dhlConfig
     * @param ShippingProducts $shippingProducts
     * @param PackagingOptionReaderInterfaceFactory $packagingOptionReaderFactory
     */
    public function __construct(
        RequestModifierInterface $coreModifier,
        ModuleConfig $config,
        ConfigInterface $dhlConfig,
        ShippingProducts $shippingProducts,
        PackagingOptionReaderInterfaceFactory $packagingOptionReaderFactory
    ) {
        $this->config = $config;
        $this->dhlConfig = $dhlConfig;
        $this->coreModifier = $coreModifier;
        $this->shippingProducts = $shippingProducts;
        $this->packagingOptionReaderFactory = $packagingOptionReaderFactory;
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
        if (!in_array($defaultProduct, $applicableProductCodes, true)) {
            $message = __(
                'The product %1 is not valid for the route %2-%3.',
                $defaultProduct,
                $originCountry,
                $destinationCountry
            );
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

        // Route within EU, no customs data to add.
        if (in_array($recipientCountry, $euCountries, true)) {
            return;
        }

        $shipment = $shipmentRequest->getOrderShipment();

        /** @var PackagingOptionReaderInterface $reader */
        $reader = $this->packagingOptionReaderFactory->create(['shipment' => $shipment]);

        $packageId = $shipmentRequest->getData('package_id');
        $package   = $shipmentRequest->getData('packages')[$packageId];

        // Customs
        $package['params']['customs']['additionalFee']
            = $reader->getPackageOptionValue('packageCustoms', 'additionalFee');
        $package['params']['customs']['placeOfCommittal']
            = $reader->getPackageOptionValue('packageCustoms', 'placeOfCommittal');
        $package['params']['customs']['electronicExportNotification']
            = $reader->getPackageOptionValue('packageCustoms', 'electronicExportNotification');

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
