<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Paket\Model\ShipmentRequest\RequestExtractorFactory;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\PackageInterface;
use Dhl\ShippingCore\Api\UnitConverterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Request mapper.
 *
 * @author Rico Sonntag <rico.sonntag@netresearch.de>
 * @link https://www.netresearch.de/
 */
class RequestDataMapper
{
    /**
     * Utility for extracting data from shipment request.
     *
     * @var RequestExtractorFactory
     */
    private $requestExtractorFactory;

    /**
     * The shipment request builder.
     *
     * @var ShipmentOrderRequestBuilderInterface
     */
    private $requestBuilder;

    /**
     * @var UnitConverterInterface
     */
    private $unitConverter;

    /**
     * RequestDataMapper constructor.
     * @param ShipmentOrderRequestBuilderInterface $requestBuilder
     * @param RequestExtractorFactory $requestExtractorFactory
     * @param UnitConverterInterface $unitConverter
     */
    public function __construct(
        ShipmentOrderRequestBuilderInterface $requestBuilder,
        RequestExtractorFactory $requestExtractorFactory,
        UnitConverterInterface $unitConverter
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->unitConverter = $unitConverter;
    }

    /**
     * Map the Magento shipment request to an SDK request object using the SDK request builder.
     *
     * @param string $sequenceNumber Request identifier to associate request-response pairs
     * @param Request $request The shipment request
     * @return object
     * @throws LocalizedException
     */
    public function mapRequest(string $sequenceNumber, Request $request)
    {
        $requestExtractor = $this->requestExtractorFactory->create([
            'shipmentRequest' => $request,
        ]);

        $this->requestBuilder->setSequenceNumber($sequenceNumber);
        $this->requestBuilder->setShipperAccount($requestExtractor->getBillingNumber());

        $this->requestBuilder->setShipperAddress(
            $requestExtractor->getShipper()->getContactCompanyName(),
            $requestExtractor->getShipper()->getCountryCode(),
            $requestExtractor->getShipper()->getPostalCode(),
            $requestExtractor->getShipper()->getCity(),
            $requestExtractor->getShipper()->getStreetName(),
            $requestExtractor->getShipper()->getStreetNumber(),
            $requestExtractor->getShipper()->getContactPersonName(),
            null,
            null,
            null,
            null,
            $requestExtractor->getShipper()->getState()
        );

        //todo(nr): check if customer did select parcel announcement service
        $this->requestBuilder->setRecipientAddress(
            $requestExtractor->getRecipient()->getContactPersonName(),
            $requestExtractor->getRecipient()->getCountryCode(),
            $requestExtractor->getRecipient()->getPostalCode(),
            $requestExtractor->getRecipient()->getCity(),
            $requestExtractor->getRecipient()->getStreetName(),
            $requestExtractor->getRecipient()->getStreetNumber(),
            $requestExtractor->getRecipient()->getContactCompanyName(),
            null,
            ($parcelAnnouncement = false) ? $requestExtractor->getRecipient()->getContactEmail() : null,
            null,
            null,
            $requestExtractor->getRecipient()->getRegionCode(),
            null,
            [$requestExtractor->getRecipient()->getAddressAddition()]
        );

        if ($requestExtractor->isPrintOnlyIfCodeable()) {
            $this->requestBuilder->setPrintOnlyIfCodeable();
        }

        // Add cash on delivery amount if COD payment method
        if ($requestExtractor->isCashOnDelivery()) {
            $this->requestBuilder->setCodAmount((float) $requestExtractor->getOrder()->getBaseGrandTotal());
        }

        /** @var PackageInterface $package */
        foreach ($requestExtractor->getPackages() as $package) {
            $this->requestBuilder->setShipmentDetails(
                $package->getProductCode(),
                $requestExtractor->getShipmentDate(),
                $requestExtractor->getOrder()->getIncrementId()
            );

            $weight = $package->getWeight();
            $weightUom = $package->getWeightUom();
            $weightInKg = $this->unitConverter->convertWeight($weight, $weightUom, \Zend_Measure_Weight::KILOGRAM);

            $this->requestBuilder->setPackageDetails($weightInKg);

            $dimensionsUom = $package->getDimensionsUom();
            $width = $package->getWidth();
            $length = $package->getLength();
            $height = $package->getHeight();

            if ($width && $length && $height) {
                $targetUom = \Zend_Measure_Length::CENTIMETER;
                $widthInCm = $this->unitConverter->convertDimension($width, $dimensionsUom, $targetUom);
                $lengthInCm = $this->unitConverter->convertDimension($length, $dimensionsUom, $targetUom);
                $heightInCm = $this->unitConverter->convertDimension($height, $dimensionsUom, $targetUom);
                $this->requestBuilder->setPackageDimensions((int) $widthInCm, (int) $lengthInCm, (int) $heightInCm);
            }
        }

        return $this->requestBuilder->create();
    }
}
