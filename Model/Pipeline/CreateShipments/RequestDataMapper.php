<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments;

use Dhl\Paket\Api\ShipmentDateInterface;
use Dhl\Paket\Model\Adminhtml\System\Config\Source\DeliveryType;
use Dhl\Paket\Model\Adminhtml\System\Config\Source\Endorsement;
use Dhl\Paket\Model\Adminhtml\System\Config\Source\VisualCheckOfAge;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Data\PackageAdditional;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\RequestExtractorFactory;
use Dhl\Paket\Model\Webservice\ShipmentOrderRequestBuilderFactory;
use Dhl\Sdk\ParcelDe\Shipping\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\Sdk\ParcelDe\Shipping\Exception\RequestValidatorException;
use Dhl\Sdk\UnifiedLocationFinder\Api\Data\LocationInterface;
use Dhl\ShippingCore\Model\Config\Source\TermsOfTrade;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Measure\Length;
use Magento\Framework\Measure\Weight;
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
     * @param int $requestIndex
     */
    private function addRequestIndex(Request $request, int $packageId, int $requestIndex): void
    {
        $packages = $request->getData('packages');

        foreach ($packages as $requestPackageId => &$package) {
            if ($requestPackageId === $packageId) {
                $package['request_index'] = $requestIndex;
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
     * @param int $requestIndex Request identifier to associate request-response pairs
     * @param Request $request The shipment request
     *
     * @return object
     * @throws LocalizedException
     */
    public function mapRequest(int $requestIndex, Request $request)
    {
        $requestExtractor = $this->requestExtractorFactory->create(['shipmentRequest' => $request]);

        $requestBuilder = $this->requestBuilderFactory->create($requestExtractor->getStoreId());
        $requestBuilder->setRequestIndex($requestIndex);


        if ($requestExtractor->isReturnShipment()) {
            $returnBillingNumber = $requestExtractor->getReturnShipmentAccountNumber();
        } else {
            $returnBillingNumber = null;
        }

        $requestBuilder->setShipperAccount($requestExtractor->getBillingNumber(), $returnBillingNumber);

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

            $this->addRequestIndex($request, $packageId, $requestIndex);
            $requestIndex++;

            $requestBuilder->setShipmentDetails(
                $package->getProductCode(),
                $this->shipmentDate->getDate($requestExtractor->getStoreId()),
                str_pad($requestExtractor->getOrder()->getIncrementId(), 8, "0", STR_PAD_LEFT)
            );

            $weight = $package->getWeight();
            $weightUom = $package->getWeightUom();
            $weightInKg = $this->unitConverter->convertWeight($weight, $weightUom, Weight::KILOGRAM);

            $requestBuilder->setPackageDetails($weightInKg);

            $dimensionsUom = $package->getDimensionsUom();
            $width = $package->getWidth();
            $length = $package->getLength();
            $height = $package->getHeight();

            if ($width || $length || $height) {
                $targetUom = Length::CENTIMETER;
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

            if ($requestExtractor->isRecipientSignature()) {
                $requestBuilder->setSignedForByRecipient();
            }

            if ($requestExtractor->isBulkyGoods()) {
                $requestBuilder->setBulkyGoods();
            }

            if ($requestExtractor->isGoGreenPlusEnabled()) {
                $requestBuilder->setGoGreenPlus();
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

                if ($requestExtractor->isGoGreenPlusEnabled()) {
                    $requestBuilder->setReturnShipmentGoGreenPlus();
                }
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
                        $requestExtractor->getRecipient()->getContactEmail(),
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

            switch ($requestExtractor->getDeliveryType()) {
                case DeliveryType::OPTION_CDP:
                    $requestBuilder->setDeliveryType(ShipmentOrderRequestBuilderInterface::DELIVERY_TYPE_CDP);
                    break;
                case DeliveryType::OPTION_ECONOMY:
                    $requestBuilder->setDeliveryType(ShipmentOrderRequestBuilderInterface::DELIVERY_TYPE_ECONOMY);
                    break;
                case DeliveryType::OPTION_PREMIUM:
                    $requestBuilder->setDeliveryType(ShipmentOrderRequestBuilderInterface::DELIVERY_TYPE_PREMIUM);
                    break;
            }

            if ($requestExtractor->isDeliveryDutyPaid()) {
                $requestBuilder->setDeliveryDutyPaid();
            }

            // customs value indicates cross-border shipment
            if ($package->getCustomsValue() !== null) {
                $termsOfTrade = match ($packageExtension->getTermsOfTrade()) {
                    TermsOfTrade::DDU => ShipmentOrderRequestBuilderInterface::INCOTERM_CODE_DAP,
                    TermsOfTrade::DDP => ShipmentOrderRequestBuilderInterface::INCOTERM_CODE_DDP,
                    default => '',
                };

                $requestBuilder->setCustomsDetails(
                    $package->getContentType(),
                    $packageExtension->getPlaceOfCommittal(),
                    $packageExtension->getCustomsFees(),
                    $package->getContentExplanation(),
                    $termsOfTrade,
                    null,
                    $packageExtension->getPermitNumber(),
                    $packageExtension->getAttestationNumber(),
                    $packageExtension->getElectronicExportNotification(),
                    $packageExtension->getSendersCustomsReference(),
                    $packageExtension->getAddresseesCustomsReference(),
                    $packageExtension->getMasterReferenceNumber()
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
