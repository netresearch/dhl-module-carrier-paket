<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments;

use Dhl\Paket\Api\ShipmentDateInterface;
use Dhl\Paket\Model\Adminhtml\System\Config\Source\Endorsement;
use Dhl\Paket\Model\Adminhtml\System\Config\Source\VisualCheckOfAge;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Data\PackageAdditional;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\RequestExtractorFactory;
use Dhl\Paket\Model\Webservice\ShipmentOrderRequestBuilderFactory;
use Dhl\Sdk\Paket\Bcs\Exception\RequestValidatorException;
use Dhl\Sdk\UnifiedLocationFinder\Api\Data\LocationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageItemInterface;
use Netresearch\ShippingCore\Api\Util\UnitConverterInterface;

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
     * @var ShipmentOrderRequestBuilderFactory
     */
    private $requestBuilderFactory;

    /**
     * @var ShipmentDateInterface
     */
    private $shipmentDate;

    /**
     * @var UnitConverterInterface
     */
    private $unitConverter;

    public function __construct(
        ShipmentOrderRequestBuilderFactory $requestBuilderFactory,
        RequestExtractorFactory $requestExtractorFactory,
        ShipmentDateInterface $shipmentDate,
        UnitConverterInterface $unitConverter
    ) {
        $this->requestBuilderFactory = $requestBuilderFactory;
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->shipmentDate = $shipmentDate;
        $this->unitConverter = $unitConverter;
    }

    /**
     * Add the generated sequence number (unique package id) to the shipment request's package for later association.
     *
     * @param Request $request
     * @param int $packageId
     * @param string $sequenceNumber
     */
    private function addSequenceNumber(Request $request, int $packageId, string $sequenceNumber): void
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
     * Convert to multi-line reason for payment if line length is exceeded.
     *
     * @param string $reasonForPayment
     * @return string[]
     */
    private function wrapReasonForPayment(string $reasonForPayment): array
    {
        // try splitting the string between words first
        $lines = explode("\n", wordwrap($reasonForPayment, 35));
        if (count($lines) < 3) {
            return $lines;
        }

        // if that did not succeed, split hard
        $lines = str_split($reasonForPayment, 35);
        array_splice($lines, 2);
        return $lines;
    }

    /**
     * Map the Magento shipment request to an SDK request object using the SDK request builder.
     *
     * @param string $sequenceNumber Request identifier to associate request-response pairs
     * @param Request $request The shipment request
     *
     * @return object
     * @throws LocalizedException
     */
    public function mapRequest(string $sequenceNumber, Request $request)
    {
        $requestExtractor = $this->requestExtractorFactory->create(['shipmentRequest' => $request]);

        $requestBuilder = $this->requestBuilderFactory->create($requestExtractor->getStoreId());
        $requestBuilder->setSequenceNumber($sequenceNumber);

        $requestBuilder->setShipperAccount(
            $requestExtractor->getBillingNumber(),
            $requestExtractor->isReturnShipment() ? $requestExtractor->getReturnShipmentAccountNumber() : null
        );

        $senderReference = $requestExtractor->getSenderReference();
        if ($senderReference) {
            $requestBuilder->setShipperReference($senderReference);
        } else {
            $requestBuilder->setShipperAddress(
                $requestExtractor->getShipper()->getContactCompanyName(),
                $requestExtractor->getShipper()->getCountryCode(),
                $requestExtractor->getShipper()->getPostalCode(),
                $requestExtractor->getShipper()->getCity(),
                $requestExtractor->getShipper()->getStreetName(),
                $requestExtractor->getShipper()->getStreetNumber(),
                null,
                null,
                null,
                null,
                null,
                $requestExtractor->getShipper()->getState()
            );
        }

        if ($requestExtractor->isRecipientEmailRequired()) {
            $recipientEmail = $requestExtractor->getRecipient()->getContactEmail();
        } else {
            $recipientEmail = null;
        }

        if ($requestExtractor->isRecipientPhoneRequired()) {
            $recipientPhone = $requestExtractor->getRecipient()->getContactPhoneNumber();
        } else {
            $recipientPhone = null;
        }

        $requestBuilder->setRecipientAddress(
            $requestExtractor->getRecipient()->getContactPersonName(),
            $requestExtractor->getRecipient()->getCountryCode(),
            $requestExtractor->getRecipient()->getPostalCode(),
            $requestExtractor->getRecipient()->getCity(),
            $requestExtractor->getRecipient()->getStreetName(),
            $requestExtractor->getRecipient()->getStreetNumber(),
            $requestExtractor->getRecipient()->getContactCompanyName(),
            null,
            $recipientEmail,
            $recipientPhone,
            null,
            $requestExtractor->getRecipient()->getRegionCode(),
            null,
            [$requestExtractor->getRecipient()->getAddressAddition()]
        );

        /** @var PackageInterface $package */
        foreach ($requestExtractor->getPackages() as $packageId => $package) {
            /** @var PackageAdditional $packageExtension */
            $packageExtension = $package->getPackageAdditional();

            //fixme(nr): request data are overridden silently for shipment requests with multiple packages
            $this->addSequenceNumber($request, $packageId, $sequenceNumber);

            $requestBuilder->setShipmentDetails(
                $package->getProductCode(),
                $this->shipmentDate->getDate($requestExtractor->getStoreId()),
                $requestExtractor->getOrder()->getIncrementId()
            );

            $weight = $package->getWeight();
            $weightUom = $package->getWeightUom();
            $weightInKg = $this->unitConverter->convertWeight($weight, $weightUom, \Zend_Measure_Weight::KILOGRAM);

            $requestBuilder->setPackageDetails($weightInKg);

            $dimensionsUom = $package->getDimensionsUom();
            $width = $package->getWidth();
            $length = $package->getLength();
            $height = $package->getHeight();

            if ($width || $length || $height) {
                $targetUom = \Zend_Measure_Length::CENTIMETER;
                $widthInCm = $this->unitConverter->convertDimension($width, $dimensionsUom, $targetUom);
                $lengthInCm = $this->unitConverter->convertDimension($length, $dimensionsUom, $targetUom);
                $heightInCm = $this->unitConverter->convertDimension($height, $dimensionsUom, $targetUom);
                $requestBuilder->setPackageDimensions(
                    (int) round($widthInCm),
                    (int) round($lengthInCm),
                    (int) round($heightInCm)
                );
            }

            $baseTotal = round((float) $requestExtractor->getOrder()->getBaseGrandTotal(), 2);
            if ($requestExtractor->isCashOnDelivery()) {
                $notes = $this->wrapReasonForPayment($requestExtractor->getCodReasonForPayment());
                $accountReference = $requestExtractor->getAccountReference();
                $requestBuilder->setShipperBankData(null, null, null, null, $accountReference, $notes);
                $requestBuilder->setCodAmount($baseTotal);
            }

            if ($requestExtractor->isAdditionalInsurance()) {
                $requestBuilder->setInsuredValue($baseTotal);
            }

            $visualCheckOfAge = $requestExtractor->getVisualCheckOfAge();
            if (in_array($visualCheckOfAge, [VisualCheckOfAge::OPTION_A16, VisualCheckOfAge::OPTION_A18], true)) {
                $requestBuilder->setVisualCheckOfAge($visualCheckOfAge);
            }

            if ($requestExtractor->isNamedPersonOnly()) {
                $requestBuilder->setNamedPersonOnly();
            }

            if ($requestExtractor->isNoNeighborDelivery()) {
                $requestBuilder->setNoNeighbourDelivery();
            }

            if ($requestExtractor->isBulkyGoods()) {
                $requestBuilder->setBulkyGoods();
            }

            if ($requestExtractor->hasPreferredDay()) {
                $requestBuilder->setPreferredDay($requestExtractor->getPreferredDay());
            }

            if ($requestExtractor->hasNeighborDelivery()) {
                $requestBuilder->setPreferredNeighbour($requestExtractor->getNeighborDetails());
            }

            if ($requestExtractor->hasDropOffLocation()) {
                $requestBuilder->setPreferredLocation($requestExtractor->getDropOffLocation());
            }

            if ($requestExtractor->isReturnShipment()) {
                $requestBuilder->setReturnAddress(
                    $requestExtractor->getReturnRecipient()->getContactCompanyName(),
                    $requestExtractor->getReturnRecipient()->getCountryCode(),
                    $requestExtractor->getReturnRecipient()->getPostalCode(),
                    $requestExtractor->getReturnRecipient()->getCity(),
                    $requestExtractor->getReturnRecipient()->getStreetName(),
                    $requestExtractor->getReturnRecipient()->getStreetNumber()
                );
            }

            $nonDeliveryNoticeEmail = $requestExtractor->getParcelOutletRoutingEmail();
            if ($nonDeliveryNoticeEmail) {
                $requestBuilder->setParcelOutletRouting($nonDeliveryNoticeEmail);
            }

            if ($requestExtractor->isPickupLocationDelivery()) {
                if ($requestExtractor->getDeliveryLocationType() === LocationInterface::TYPE_LOCKER) {
                    $requestBuilder->setPackstation(
                        $requestExtractor->getRecipient()->getContactPersonName(),
                        $requestExtractor->getCustomerAccountNumber(),
                        $requestExtractor->getDeliveryLocationNumber(),
                        $requestExtractor->getDeliveryLocationCountryCode(),
                        $requestExtractor->getDeliveryLocationPostalCode(),
                        $requestExtractor->getDeliveryLocationCity()
                    );
                } else {
                    $requestBuilder->setPostfiliale(
                        $requestExtractor->getRecipient()->getContactPersonName(),
                        $requestExtractor->getDeliveryLocationNumber(),
                        $requestExtractor->getDeliveryLocationCountryCode(),
                        $requestExtractor->getDeliveryLocationPostalCode(),
                        $requestExtractor->getDeliveryLocationCity(),
                        $requestExtractor->getCustomerAccountNumber()
                    );
                }
            }

            $endorsement = $requestExtractor->getEndorsement();
            if ($endorsement === Endorsement::OPTION_ABANDON) {
                $requestBuilder->setShipmentEndorsementType('ABANDONMENT');
            } elseif ($endorsement === Endorsement::OPTION_RETURN) {
                $requestBuilder->setShipmentEndorsementType('IMMEDIATE');
            }

            if ($requestExtractor->isPremium()) {
                $requestBuilder->setPremium();
            }

            if ($package->getCustomsValue() !== null) {
                // customs value indicates cross-border shipment
                $requestBuilder->setCustomsDetails(
                    $package->getContentType(),
                    $packageExtension->getPlaceOfCommittal(),
                    $packageExtension->getCustomsFees(),
                    $package->getContentExplanation(),
                    $packageExtension->getTermsOfTrade(),
                    null,
                    $packageExtension->getPermitNumber(),
                    $packageExtension->getAttestationNumber(),
                    $packageExtension->getElectronicExportNotification(),
                    $packageExtension->getSendersCustomsReference(),
                    $packageExtension->getAddresseesCustomsReference()
                );

                /** @var PackageItemInterface $packageItem */
                foreach ($requestExtractor->getPackageItems() as $packageItem) {
                    $requestBuilder->addExportItem(
                        (int)round($packageItem->getQty()),
                        $packageItem->getExportDescription(),
                        $packageItem->getCustomsValue(),
                        $packageItem->getWeight(),
                        $packageItem->getHsCode(),
                        $packageItem->getCountryOfOrigin()
                    );
                }
            }
        }

        try {
            return $requestBuilder->create();
        } catch (RequestValidatorException $exception) {
            $message = __('Web service request could not be created: %1', $exception->getMessage());
            throw new LocalizedException($message);
        }
    }
}
