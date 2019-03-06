<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\ShippingCore\Model\Config\CoreConfigInterface;
use Dhl\ShippingCore\Util\StreetSplitterInterface;
use Dhl\ShippingCore\Util\UnitConverterInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Convert shipment request to shipment order.
 *
 * @deprecated | convert shipment request to shipment order using an "extractor" in DHL shipping core.
 *
 * @inheritDoc
 */
class RequestDataMapper implements RequestDataMapperInterface
{
    /**
     * @var ShipmentOrderRequestBuilderInterface
     */
    private $requestBuilder;

    /**
     * @var CoreConfigInterface
     */
    private $shippingConfig;

    /**
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
     * @var UnitConverterInterface
     */
    private $unitConverter;

    /**
     * RequestDataMapper constructor.
     * @param ShipmentOrderRequestBuilderInterface $requestBuilder
     * @param CoreConfigInterface $shippingConfig
     * @param ModuleConfig $moduleConfig
     * @param StreetSplitterInterface $streetSplitter
     * @param TimezoneInterface $timezone
     * @param ShippingProductsInterface $shippingProducts
     * @param UnitConverterInterface $unitConverter
     */
    public function __construct(
        ShipmentOrderRequestBuilderInterface $requestBuilder,
        CoreConfigInterface $shippingConfig,
        ModuleConfig $moduleConfig,
        StreetSplitterInterface $streetSplitter,
        TimezoneInterface $timezone,
        ShippingProductsInterface $shippingProducts,
        UnitConverterInterface $unitConverter
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->shippingConfig = $shippingConfig;
        $this->moduleConfig = $moduleConfig;
        $this->streetSplitter = $streetSplitter;
        $this->timezone = $timezone;
        $this->shippingProducts = $shippingProducts;
        $this->unitConverter = $unitConverter;
    }

    /**
     * @inheritDoc
     */
    public function mapRequest(Request $request)
    {
        // Split address into street name and street number as required by the webservice
        $shipperAddress = $this->streetSplitter->splitStreet($request->getShipperAddressStreet());
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
            (string)$request->getRecipientAddressPostalCode(),
            $request->getRecipientAddressCity(),
            $receiverAddress['street_name'],
            $receiverAddress['street_number']
        );

        $orderShipment = $request->getOrderShipment();
        $order         = $orderShipment->getOrder();
        $storeId       = $orderShipment->getStoreId();

        $this->requestBuilder->setShipmentDetails(
            $this->getProductCode($request),
            $this->getShipmentDate(),
            $order->getIncrementId()
        );

        $weight = (float) $request->getPackageParams()->getWeight();
        $weightUom = $request->getPackageParams()->getWeightUnits();
        $weightInKg = $this->unitConverter->convertWeight($weight, $weightUom, \Zend_Measure_Weight::KILOGRAM);

        $this->requestBuilder->setPackageDetails($weightInKg);

        $dimensionsUom = $request->getPackageParams()->getDimensionUnits();
        $width = (float) $request->getPackageParams()->getWidth();
        $length = (float) $request->getPackageParams()->getLength();
        $height = (float) $request->getPackageParams()->getHeight();
        $widthInCm = $this->unitConverter->convertDimension($width, $dimensionsUom, \Zend_Measure_Length::CENTIMETER);
        $lengthInCm = $this->unitConverter->convertDimension($length, $dimensionsUom, \Zend_Measure_Length::CENTIMETER);
        $heightInCm = $this->unitConverter->convertDimension($height, $dimensionsUom, \Zend_Measure_Length::CENTIMETER);

        $this->requestBuilder->setPackageDimensions((int) $widthInCm, (int) $lengthInCm, (int) $heightInCm);

        if ($this->moduleConfig->printOnlyIfCodeable($storeId)) {
            $this->requestBuilder->setPrintOnlyIfCodeable();
        }

        // Add cash on delivery amount if COD payment method
        $payment = $order->getPayment();
        if ($this->shippingConfig->isCodPaymentMethod($payment->getMethod(), $storeId)) {
            $this->requestBuilder->setCodAmount((float) $order->getBaseGrandTotal());
        }

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
        return $request->getData('packaging_type');
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
        $storeId = $request->getOrderShipment()->getStoreId();
        $productCode = $this->getProductCode($request);
        $ekp = $this->moduleConfig->getEkp($storeId);
        $participations = $this->moduleConfig->getParticipations($storeId);

        return $this->shippingProducts->getBillingNumber($productCode, $ekp, $participations);
    }

    /**
     * Returns the shipment date.
     *
     * fixme(nr): "tomorrow" is not the correct shipment date
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
