<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Paket\Model\Config\ModuleConfigInterface;
use Dhl\Sdk\Bcs\Api\Data\ShipmentRequestInterface;
use Dhl\Sdk\Bcs\Api\ShipmentRequestBuilderInterface;
use Dhl\ShippingCore\Model\Config\CoreConfigInterface;
use Dhl\ShippingCore\Util\StreetSplitter;
use Dhl\ShippingCore\Util\StreetSplitterInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Shipping\Model\Shipment\Request;

/**
 * @inheritDoc
 */
class RequestDataMapper implements RequestDataMapperInterface
{
    /**
     * The shipment request builder.
     *
     * @var ShipmentRequestBuilderInterface
     */
    private $requestBuilder;

    /**
     * The module configuration.
     *
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * The shipping configuration.
     *
     * @var CoreConfigInterface
     */
    private $shippingConfig;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var StreetSplitterInterface
     */
    private $streetSplitter;

    /**
     * Constructor.
     *
     * @param ShipmentRequestBuilderInterface $requestBuilder
     * @param ModuleConfigInterface           $moduleConfig
     * @param CoreConfigInterface             $shippingConfig
     * @param DataObjectFactory               $dataObjectFactory
     * @param StreetSplitterInterface         $streetSplitter
     */
    public function __construct(
        ShipmentRequestBuilderInterface $requestBuilder,
        ModuleConfigInterface $moduleConfig,
        CoreConfigInterface $shippingConfig,
        DataObjectFactory $dataObjectFactory,
        StreetSplitterInterface $streetSplitter
    ) {
        $this->requestBuilder    = $requestBuilder;
        $this->moduleConfig      = $moduleConfig;
        $this->shippingConfig    = $shippingConfig;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->streetSplitter    = $streetSplitter;
    }

    /**
     * @inheritDoc
     */
    public function mapRequest(Request $request): ShipmentRequestInterface
    {
        // Split address into street name and street number as required by the webservice
        $shipperAddress  = $this->streetSplitter->splitStreet($request->getShipperAddressStreet());
        $receiverAddress = $this->streetSplitter->splitStreet($request->getRecipientAddressStreet());

        $this->requestBuilder
            ->setShipperAddress(
                $request->getShipperContactPersonName(),
                $shipperAddress['street_name'],
                $shipperAddress['street_number'],
                $request->getShipperAddressPostalCode(),
                $request->getShipperAddressCity(),
                $request->getShipperAddressCountryCode()
            );

        $this->requestBuilder
            ->setReceiverAddress(
                $request->getRecipientContactPersonName(),
                $receiverAddress['street_name'],
                $receiverAddress['street_number'],
                $request->getRecipientAddressPostalCode(),
                $request->getRecipientAddressCity(),
                $request->getRecipientAddressCountryCode()
            );

        // TODO Use values from config, Calculate shipment date?
        $this->requestBuilder->setShipmentDetails(
            $this->moduleConfig->getProduct(),
            $this->moduleConfig->getAccountNumber(),
            '', //$this->moduleConfig->getShipmentDate(),
            (float) $request->getPackageParams()->getWeight(),
            (int) $request->getPackageParams()->getLength(),
            (int) $request->getPackageParams()->getWidth(),
            (int) $request->getPackageParams()->getHeight()
        );

        $this->requestBuilder
            ->setShipmentOrder(ShipmentRequestBuilderInterface::LABEL_RESPONSE_TYPE_B64);

        return $this->requestBuilder->build();
    }
}
