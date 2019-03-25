<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Paket\Model\ShipmentRequest\RequestExtractor;
use Dhl\Paket\Model\ShipmentRequest\RequestExtractorFactory;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\ShippingCore\Util\UnitConverterInterface;
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
     * @var RequestExtractor
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
     * @inheritDoc
     *
     * @throws LocalizedException
     * @throws \ReflectionException
     */
    public function mapRequest(Request $request)
    {
        $requestExtractor = $this->requestExtractorFactory->create([
            'shipmentRequest' => $request,
        ]);

        $this->requestBuilder->setShipperAccount($requestExtractor->getBillingNumber());

        //todo(nr): add "address addition" from split street
        $this->requestBuilder->setShipperAddress(
            $requestExtractor->getShipper()->getContactCompanyName(),
            $requestExtractor->getShipper()->getCountryCode(),
            $requestExtractor->getShipper()->getPostalCode(),
            $requestExtractor->getShipper()->getCity(),
            $requestExtractor->getShipper()->getStreetName(),
            $requestExtractor->getShipper()->getStreetNumber(),
            $requestExtractor->getShipper()->getContactPersonName(),
            null,
            $requestExtractor->getShipper()->getContactEmail(),
            $requestExtractor->getShipper()->getContactPhoneNumber(),
            null,
            $requestExtractor->getShipper()->getState()
        );

        //todo(nr): add "address addition" from split street
        $this->requestBuilder->setRecipientAddress(
            $requestExtractor->getRecipient()->getContactPersonName(),
            $requestExtractor->getRecipient()->getCountryCode(),
            $requestExtractor->getRecipient()->getPostalCode(),
            $requestExtractor->getRecipient()->getCity(),
            $requestExtractor->getRecipient()->getStreetName(),
            $requestExtractor->getRecipient()->getStreetNumber(),
            $requestExtractor->getRecipient()->getContactCompanyName(),
            null,
            $requestExtractor->getRecipient()->getContactEmail(),
            $requestExtractor->getRecipient()->getContactPhoneNumber(),
            null,
            $requestExtractor->getRecipient()->getRegionCode()
        );

        $this->requestBuilder->setShipmentDetails(
            $requestExtractor->getPackage()->getProductCode(),
            $requestExtractor->getShipmentDate(),
            $requestExtractor->getOrder()->getIncrementId()
        );

        $weight = (float) $requestExtractor->getPackage()->getWeight();
        $weightUom = $requestExtractor->getPackage()->getWeightUom();
        $weightInKg = $this->unitConverter->convertWeight($weight, $weightUom, \Zend_Measure_Weight::KILOGRAM);

        $this->requestBuilder->setPackageDetails($weightInKg);

        $dimensionsUom = $requestExtractor->getPackage()->getDimensionsUom();
        $width = (float) $requestExtractor->getPackage()->getWidth();
        $length = (float) $requestExtractor->getPackage()->getLength();
        $height = (float) $requestExtractor->getPackage()->getHeight();
        $widthInCm = $this->unitConverter->convertDimension($width, $dimensionsUom, \Zend_Measure_Length::CENTIMETER);
        $lengthInCm = $this->unitConverter->convertDimension($length, $dimensionsUom, \Zend_Measure_Length::CENTIMETER);
        $heightInCm = $this->unitConverter->convertDimension($height, $dimensionsUom, \Zend_Measure_Length::CENTIMETER);

        $this->requestBuilder->setPackageDimensions((int) $widthInCm, (int) $lengthInCm, (int) $heightInCm);

        if ($requestExtractor->isPrintOnlyIfCodeable()) {
            $this->requestBuilder->setPrintOnlyIfCodeable();
        }

        // Add cash on delivery amount if COD payment method
        if ($requestExtractor->isCashOnDelivery()) {
            $this->requestBuilder->setCodAmount((float) $requestExtractor->getOrder()->getBaseGrandTotal());
        }

        return $this->requestBuilder->create();
    }
}
