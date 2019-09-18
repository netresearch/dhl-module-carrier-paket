<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Pipeline\CreateShipments;

use Dhl\Paket\Model\ShipmentRequest\PackageAdditional;
use Dhl\Paket\Model\ShipmentRequest\RequestExtractor;
use Dhl\Paket\Model\ShipmentRequest\RequestExtractorFactory;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\Sdk\Paket\Bcs\Model\CreateShipment\RequestType\ShipmentOrderType;
use Dhl\ShippingCore\Api\ConfigInterface;
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
     * @var ConfigInterface
     */
    private $dhlConfig;

    /**
     * @var UnitConverterInterface
     */
    private $unitConverter;

    /**
     * RequestDataMapper constructor.
     *
     * @param ShipmentOrderRequestBuilderInterface $requestBuilder
     * @param RequestExtractorFactory $requestExtractorFactory
     * @param ConfigInterface $dhlConfig
     * @param UnitConverterInterface $unitConverter
     */
    public function __construct(
        ShipmentOrderRequestBuilderInterface $requestBuilder,
        RequestExtractorFactory $requestExtractorFactory,
        ConfigInterface $dhlConfig,
        UnitConverterInterface $unitConverter
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->dhlConfig = $dhlConfig;
        $this->unitConverter = $unitConverter;
    }

    /**
     * Add the generated sequence number (unique package id) to the shipment request's package for later association.
     *
     * @param Request $request
     * @param int $packageId
     * @param string $sequenceNumber
     */
    private function addSequenceNumber(Request $request, int $packageId, string $sequenceNumber)
    {
        $packages = $request->getData('packages');

        foreach ($packages as $requestPackageId => &$package) {
            if ($requestPackageId === $packageId) {
                $package['sequence_number'] = $sequenceNumber;
            }
        }

        $request->setData('packages', $packages);
    }

    /**
     * Map the Magento shipment request to an SDK request object using the SDK request builder.
     *
     * @param string $sequenceNumber Request identifier to associate request-response pairs
     * @param Request $request The shipment request
     * @return ShipmentOrderType
     * @throws LocalizedException
     */
    public function mapRequest(string $sequenceNumber, Request $request): ShipmentOrderType
    {
        /** @var RequestExtractor $requestExtractor */
        $requestExtractor = $this->requestExtractorFactory->create(
            [
                'shipmentRequest' => $request,
            ]
        );

        $this->requestBuilder->setSequenceNumber($sequenceNumber);
        $this->requestBuilder->setShipperAccount(
            $requestExtractor->getBillingNumber(),
            $requestExtractor->getReturnShipmentAccountNumber()
        );

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

        // add email if parcel announcement is selected
        $recipientEmail = null;
        if ($requestExtractor->isParcelAnnouncement()) {
            $recipientEmail = $requestExtractor->getRecipient()->getContactEmail();
        }

        $this->requestBuilder->setRecipientAddress(
            $requestExtractor->getRecipient()->getContactPersonName(),
            $requestExtractor->getRecipient()->getCountryCode(),
            $requestExtractor->getRecipient()->getPostalCode(),
            $requestExtractor->getRecipient()->getCity(),
            $requestExtractor->getRecipient()->getStreetName(),
            $requestExtractor->getRecipient()->getStreetNumber(),
            $requestExtractor->getRecipient()->getContactCompanyName(),
            null,
            $recipientEmail,
            null,
            null,
            $requestExtractor->getRecipient()->getRegionCode(),
            null,
            [$requestExtractor->getRecipient()->getAddressAddition()]
        );

        foreach ($requestExtractor->getPackages() as $packageId => $package) {
            /** @var PackageAdditional $packageExtension */
            $packageExtension = $package->getPackageAdditional();

            //fixme(nr): request data are overridden silently for shipment requests with multiple packages
            $this->addSequenceNumber($request, $packageId, $sequenceNumber);

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

            if ($requestExtractor->isPrintOnlyIfCodeable()) {
                $this->requestBuilder->setPrintOnlyIfCodeable();
            }

            // Add cash on delivery amount if COD payment method
            if ($requestExtractor->isCashOnDelivery()) {
                $this->requestBuilder->setCodAmount((float) $requestExtractor->getOrder()->getBaseGrandTotal());
            }

            if ($requestExtractor->isAdditionalInsurance()) {
                $this->requestBuilder->setInsuredValue((float) $requestExtractor->getOrder()->getBaseGrandTotal());
            }

            if ($requestExtractor->isVisualCheckOfAge()) {
                $this->requestBuilder->setVisualCheckOfAge($requestExtractor->getVisualCheckOfAge());
            }

            if ($requestExtractor->isBulkyGoods()) {
                $this->requestBuilder->setBulkyGoods();
            }

            if ($requestExtractor->hasPreferredTime()) {
                $this->requestBuilder->setPreferredTime($requestExtractor->getPreferredTime());
            }

            if ($requestExtractor->hasPreferredDay()) {
                $this->requestBuilder->setPreferredDay($requestExtractor->getPreferredDay());
            }

            if ($requestExtractor->hasPreferredNeighbour()) {
                $this->requestBuilder->setPreferredNeighbour(
                    $requestExtractor->getPreferredNeighbourName()
                    . $requestExtractor->getPreferredNeighbourAddress()
                );
            }

            if ($requestExtractor->hasPreferredLocation()) {
                $this->requestBuilder->setPreferredLocation($requestExtractor->getPreferredLocation());
            }

            if ($requestExtractor->isReturnShipment()) {
                $this->requestBuilder->setReturnAddress(
                    $requestExtractor->getShipper()->getContactCompanyName(),
                    $requestExtractor->getShipper()->getCountryCode(),
                    $requestExtractor->getShipper()->getPostalCode(),
                    $requestExtractor->getShipper()->getCity(),
                    $requestExtractor->getShipper()->getStreetName(),
                    $requestExtractor->getShipper()->getStreetNumber(),
                    null,
                    null,
                    $requestExtractor->getShipper()->getContactEmail(),
                    $requestExtractor->getShipper()->getContactPhoneNumber(),
                    $requestExtractor->getShipper()->getContactPersonName(),
                    $requestExtractor->getShipper()->getState(),
                    null,
                    []
                );
            }

            if ($requestExtractor->isParcelOutletRouting()) {
                $this->requestBuilder->setParcelOutletRouting($requestExtractor->getRecipient()->getContactEmail());
            }

            if ($requestExtractor->isPackstationDelivery()) {
                $packstation = $requestExtractor->getPackstationId();
                list($stationId, $countryId, $postalCode, $city) = explode('|', $packstation);

                $this->requestBuilder->setPackstation(
                    $requestExtractor->getRecipient()->getContactPersonName(),
                    $stationId,
                    $postalCode,
                    $city,
                    $requestExtractor->getPostNumber(),
                    null,
                    $countryId
                );
            }

            //todo(nr): once we added postFiliale support we need to add it here

            //todo(nr): update/remove this condition once intl options are removed from domestic packaging popup
            $isEuShipping = in_array(
                $requestExtractor->getRecipient()->getCountryCode(),
                $this->dhlConfig->getEuCountries($request->getOrderShipment()->getStoreId()),
                true
            );

            if (!$isEuShipping) {
                $this->requestBuilder->setCustomsDetails(
                    $package->getContentType(),
                    $packageExtension->getPlaceOfCommittal(),
                    $packageExtension->getAdditionalFee(),
                    $package->getContentExplanation(),
                    $package->getTermsOfTrade(),
                    null,
                    $packageExtension->getPermitNumber(),
                    $packageExtension->getAttestationNumber(),
                    $packageExtension->getElectronicExportNotification()
                );

                foreach ($requestExtractor->getPackageItems() as $packageItem) {
                    $this->requestBuilder->addExportItem(
                        (int) round($packageItem->getQty()),
                        $packageItem->getExportDescription(),
                        $packageItem->getCustomsValue(),
                        $packageItem->getWeight(),
                        $packageItem->getHsCode(),
                        $packageItem->getCountryOfOrigin()
                    );
                }
            }
        }

        return $this->requestBuilder->create();
    }
}
