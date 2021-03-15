<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestModifier\PackagingOptionReaderInterfaceFactory;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestModifierInterface;

class RequestModifier implements RequestModifierInterface
{
    /**
     * @var RequestModifierInterface
     */
    private $coreModifier;

    /**
     * @var ShippingConfigInterface
     */
    private $config;

    /**
     * @var PackagingOptionReaderInterfaceFactory
     */
    private $packagingOptionReaderFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    public function __construct(
        RequestModifierInterface $coreModifier,
        ShippingConfigInterface $config,
        PackagingOptionReaderInterfaceFactory $packagingOptionReaderFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->coreModifier = $coreModifier;
        $this->config = $config;
        $this->packagingOptionReaderFactory = $packagingOptionReaderFactory;
        $this->dataObjectFactory = $dataObjectFactory;
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
        $euCountries = $this->config->getEuCountries($shipmentRequest->getOrderShipment()->getStoreId());

        // Route within EU, no customs data to add.
        if (in_array($recipientCountry, $euCountries, true)) {
            return;
        }

        $reader = $this->packagingOptionReaderFactory->create(['shipment' => $shipmentRequest->getOrderShipment()]);

        $packages = [];
        foreach ($shipmentRequest->getData('packages') as $packageId => $package) {
            $package['params']['customs']['customsFees']
                = $reader->getPackageOptionValue('packageCustoms', 'customsFees');
            $package['params']['customs']['placeOfCommittal']
                = $reader->getPackageOptionValue('packageCustoms', 'placeOfCommittal');
            $package['params']['customs']['electronicExportNotification']
                = $reader->getPackageOptionValue('packageCustoms', 'electronicExportNotification');

            $packages[$packageId] = $package;
        }

        // set all updated packages to request
        $shipmentRequest->setData('packages', $packages);

        // add current package's params to request (compare AbstractCarrierOnline::requestToShipment)
        $package = $packages[$shipmentRequest->getData('package_id')];
        $shipmentRequest->setData('package_params', $this->dataObjectFactory->create(['data' => $package['params']]));
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
    public function modify(Request $shipmentRequest): void
    {
        // add carrier-agnostic data
        $this->coreModifier->modify($shipmentRequest);
        $this->modifyCustoms($shipmentRequest);
    }
}
