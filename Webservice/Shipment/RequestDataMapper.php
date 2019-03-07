<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\ShippingCore\Util\StreetSplitterInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Shipping\Model\Shipment\Request;
use Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface;

/**
 * @inheritDoc
 */
class RequestDataMapper implements RequestDataMapperInterface
{
    /**
     * The shipment request builder.
     *
     * @var ShipmentOrderRequestBuilderInterface
     */
    private $requestBuilder;

    /**
     * The module configuration.
     *
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var StreetSplitterInterface
     */
    private $streetSplitter;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var ShippingProductsInterface
     */
    private $shippingProducts;

    /**
     * Constructor.
     *
     * @param ShipmentOrderRequestBuilderInterface $requestBuilder
     * @param ModuleConfig           $moduleConfig
     * @param StreetSplitterInterface         $streetSplitter
     * @param TimezoneInterface               $timezone
     * @param ShippingProductsInterface       $shippingProducts
     */
    public function __construct(
        ShipmentOrderRequestBuilderInterface $requestBuilder,
        ModuleConfig $moduleConfig,
        StreetSplitterInterface $streetSplitter,
        TimezoneInterface $timezone,
        ShippingProductsInterface $shippingProducts
    ) {
        $this->requestBuilder   = $requestBuilder;
        $this->moduleConfig     = $moduleConfig;
        $this->streetSplitter   = $streetSplitter;
        $this->timezone         = $timezone;
        $this->shippingProducts = $shippingProducts;
    }

    /**
     * @inheritDoc
     */
    public function mapRequest(Request $request)
    {
        // Split address into street name and street number as required by the webservice
        $shipperAddress  = $this->streetSplitter->splitStreet($request->getShipperAddressStreet());
        $receiverAddress = $this->streetSplitter->splitStreet($request->getRecipientAddressStreet());

        $this->requestBuilder->setShipperAccount($this->getBillingNumber($request));

        $this->requestBuilder->setShipperAddress(
            $request->getShipperContactCompanyName(),
            $request->getShipperAddressCountryCode(),
            $request->getShipperAddressPostalCode(),
            $request->getShipperAddressCity(),
            $shipperAddress['street_name'],
            $shipperAddress['street_number'],
            $request->getShipperContactPersonName()
        );

        $this->requestBuilder->setRecipientAddress(
            $request->getRecipientContactPersonName(),
            $request->getRecipientAddressCountryCode(),
            (string) $request->getRecipientAddressPostalCode(),
            $request->getRecipientAddressCity(),
            $receiverAddress['street_name'],
            $receiverAddress['street_number']
        );

        $orderShipment = $request->getOrderShipment();
        $order = $orderShipment->getOrder();

        $this->requestBuilder->setShipmentDetails(
            $this->getProductCode($request),
            $this->getShipmentDate(),
            $order->getIncrementId()
        );
        $this->requestBuilder->setPackageDetails((float) $request->getPackageParams()->getWeight());

        $this->requestBuilder->setPackageDimensions(
            (int) $request->getPackageParams()->getWidth(),
            (int) $request->getPackageParams()->getLength(),
            (int) $request->getPackageParams()->getHeight()
        );

        return $this->requestBuilder->create();
    }

    /**
     * Returns the selected product code.
     *
     * @param Request $request The shipment request
     *
     * @return string
     */
    private function getProductCode(Request $request): string
    {
        return  $request->getData('packaging_type');
    }

    /**
     * Returns the 14-digit encoded billing number.
     *
     * @param Request $request The shipment request
     *
     * @return string
     */
    private function getBillingNumber(Request $request): string
    {
        $storeId        = $request->getOrderShipment()->getStoreId();
        $productCode    = $this->getProductCode($request);
        $ekp            = $this->moduleConfig->getAccountNumber($storeId);
        $participations = $this->moduleConfig->getAccountParticipations($storeId);

        return $this->shippingProducts->getBillingNumber($productCode, $ekp, $participations);
    }

    /**
     * Returns the shipment date.
     *
     * @return string
     */
    private function getShipmentDate(): string
    {
        $shipmentDate = $this->timezone->date();
        $shipmentDate->modify('+1 day');

        return $shipmentDate->format('Y-m-d');
    }
}
