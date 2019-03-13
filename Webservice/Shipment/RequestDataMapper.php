<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\ShippingCore\Util\StreetSplitterInterface;
use Dhl\ShippingCore\Util\UnitConverterInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Shipping\Model\Shipment\Request;

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
     * @var UnitConverterInterface
     */
    private $unitConverter;

    /**
     * RequestDataMapper constructor.
     * @param ShipmentOrderRequestBuilderInterface $requestBuilder
     * @param ModuleConfig $moduleConfig
     * @param StreetSplitterInterface $streetSplitter
     * @param TimezoneInterface $timezone
     * @param ShippingProductsInterface $shippingProducts
     * @param UnitConverterInterface $unitConverter
     */
    public function __construct(
        ShipmentOrderRequestBuilderInterface $requestBuilder,
        ModuleConfig $moduleConfig,
        StreetSplitterInterface $streetSplitter,
        TimezoneInterface $timezone,
        ShippingProductsInterface $shippingProducts,
        UnitConverterInterface $unitConverter
    ) {
        $this->requestBuilder   = $requestBuilder;
        $this->moduleConfig     = $moduleConfig;
        $this->streetSplitter   = $streetSplitter;
        $this->timezone         = $timezone;
        $this->shippingProducts = $shippingProducts;
        $this->unitConverter    = $unitConverter;
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
        $order         = $orderShipment->getOrder();
        $storeId       = $orderShipment->getStoreId();

        $this->requestBuilder->setShipmentDetails(
            $this->getProductCode($request),
            $this->getShipmentDate(),
            $order->getIncrementId()
        );

        $weight = (float) $request->getPackageParams()->getWeight();
        $weightUom = $request->getPackageParams()->getWeightUnits();
        $this->requestBuilder->setPackageDetails($this->getWeightInKilograms($weight, $weightUom));

        $dimensionUnits = $request->getPackageParams()->getDimensionUnits();
        $width = (float)$request->getPackageParams()->getWidth();
        $length = (float)$request->getPackageParams()->getLength();
        $height = (float)$request->getPackageParams()->getHeight();

        $this->requestBuilder->setPackageDimensions(
            $this->getDimensionInCentimeter($width, $dimensionUnits),
            $this->getDimensionInCentimeter($length, $dimensionUnits),
            $this->getDimensionInCentimeter($height, $dimensionUnits)
        );

        if ($this->moduleConfig->printOnlyIfCodeable($storeId)) {
            $this->requestBuilder->setPrintOnlyIfCodeable();
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

    /**
     * Convert weight to Kilograms.
     *
     * @param float $weightValue
     * @param string $weightUom
     * @return float
     */
    private function getWeightInKilograms(float $weightValue, string $weightUom): float
    {
        //@todo(nr) use other constant and move ayway from Zf1
        $weightConverted = $this->unitConverter->convertWeight(
            $weightValue,
            $weightUom,
            \Zend_Measure_Weight::KILOGRAM
        );

        return $weightConverted;
    }

    /**
     * Convert dimension in centimeter.
     *
     * @param $dimensionValue
     * @param $dimensionUnit
     * @return int
     */
    private function getDimensionInCentimeter(float $dimensionValue, string $dimensionUnit): int
    {
        //@todo(nr) use other constant and move ayway from Zf1
        $dimensionConverted = $this->unitConverter->convertDimension(
            $dimensionValue,
            $dimensionUnit,
            \Zend_Measure_Length::CENTIMETER
        );

        return (int) $dimensionConverted;
    }
}
