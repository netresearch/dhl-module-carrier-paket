<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Api\ShipmentDateInterface;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Model\Webservice\ParcelManagementServiceFactory;
use Dhl\Sdk\Paket\ParcelManagement\Exception\ServiceException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;

class ParcelManagementOptionsProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ShipmentDateInterface
     */
    private $shipmentDate;

    /**
     * @var ParcelManagementServiceFactory
     */
    private $serviceFactory;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    public function __construct(
        ShipmentDateInterface $shipmentDate,
        ParcelManagementServiceFactory $serviceFactory,
        OptionInterfaceFactory $optionFactory
    ) {
        $this->shipmentDate = $shipmentDate;
        $this->serviceFactory = $serviceFactory;
        $this->optionFactory = $optionFactory;
    }

    private function getOptionInput(ShippingOptionInterface $serviceOption, string $inputCode): ?InputInterface
    {
        foreach ($serviceOption->getInputs() as $input) {
            if ($input->getCode() === $inputCode) {
                return $input;
            }
        }

        return null;
    }

    private function getServiceOptions(int $storeId, string $postalCode, string $serviceCode): array
    {
        try {
            $startDate = $this->shipmentDate->getDate($storeId);

            $parcelManagementService = $this->serviceFactory->create($storeId);
            $services = $parcelManagementService->getCarrierServices($postalCode, $startDate);
        } catch (LocalizedException | ServiceException) {
            // unable to determine start date, no valid data can be fetched
            return [];
        }

        foreach ($services as $service) {
            if ($service->getCode() === $serviceCode) {
                return $service->isAvailable() ? $service->getOptions() : [];
            }
        }

        return [];
    }

    /**
     * Add dates to the preferred day service.
     *
     * There are no other services supported currently that require
     * options from the Parcel Management API.
     *
     * Label formatting is done in separate processor.
     * @see UpdatePreferredDayOptionLabelProcessor
     *
     * @param string $carrierCode
     * @param ShippingOptionInterface[] $shippingOptions
     * @param int $storeId
     * @param string $countryCode
     * @param string $postalCode
     * @param ShipmentInterface|null $shipment
     *
     * @return ShippingOptionInterface[]
     */
    #[\Override]
    public function process(
        string $carrierCode,
        array $shippingOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ?ShipmentInterface $shipment = null
    ): array {
        if ($carrierCode !== Paket::CARRIER_CODE) {
            // different carrier, nothing to modify.
            return $shippingOptions;
        }

        $serviceCode = Codes::SERVICE_OPTION_PREFERRED_DAY;

        $preferredDayOption = $shippingOptions[$serviceCode] ?? false;
        if (!$preferredDayOption) {
            // service not available for selection, nothing to modify.
            return $shippingOptions;
        }

        $dateInput = $this->getOptionInput($preferredDayOption, 'date');
        $serviceOptions = $this->getServiceOptions($storeId, $postalCode, $serviceCode);
        if (!$dateInput || empty($serviceOptions)) {
            unset($shippingOptions[$serviceCode]);
            return $shippingOptions;
        }

        // convert service options to input options
        $inputOptions = $dateInput->getOptions();
        foreach ($serviceOptions as $serviceOption) {
            try {
                $inputOption = $this->optionFactory->create();
                $inputOption->setValue((new \DateTime($serviceOption->getStart()))->format('Y-m-d'));
            } catch (\Exception) {
                continue;
            }

            $inputOptions[] = $inputOption;
        }
        $dateInput->setOptions($inputOptions);

        return $shippingOptions;
    }
}
