<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Checkout\ServiceOptions;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Model\Webservice\ParcelManagementServiceFactory;
use Dhl\Sdk\Paket\ParcelManagement\Api\Data\CarrierServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Api\Data\IntervalOptionInterface;
use Dhl\Sdk\Paket\ParcelManagement\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Checkout\ShippingOptionsProcessorInterface;
use Dhl\ShippingCore\Model\ShipmentDate\ShipmentDate;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

class ParcelManagementOptionsProcessor implements ShippingOptionsProcessorInterface
{
    const SAME_DAY_DELIVERY = 'sameDayDelivery';

    const SERVICES_WITH_OPTIONS = [
        Codes::CHECKOUT_SERVICE_PREFERRED_DAY,
        self::SAME_DAY_DELIVERY,
    ];

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * @var ParcelManagementServiceFactory
     */
    private $serviceFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var ShipmentDate
     */
    private $shipmentDate;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ParcelManagementOptionsProcessor constructor.
     *
     * @param OptionInterfaceFactory $optionFactory
     * @param ParcelManagementServiceFactory $serviceFactory
     * @param TimezoneInterface $timezone
     * @param ShipmentDate $shipmentDate
     * @param LoggerInterface $logger
     */
    public function __construct(
        OptionInterfaceFactory $optionFactory,
        ParcelManagementServiceFactory $serviceFactory,
        TimezoneInterface $timezone,
        ShipmentDate $shipmentDate,
        LoggerInterface $logger
    ) {
        $this->optionFactory = $optionFactory;
        $this->serviceFactory = $serviceFactory;
        $this->timezone = $timezone;
        $this->shipmentDate = $shipmentDate;
        $this->logger = $logger;
    }

    /**
     * Retrieve day options from API response and add to form input.
     *
     * @param ShippingOptionInterface $shippingOption
     * @param CarrierServiceInterface $carrierService
     * @param int|null $storeId
     * @throws \RuntimeException
     */
    private function addPreferredDayOptions(
        ShippingOptionInterface $shippingOption,
        CarrierServiceInterface $carrierService,
        int $storeId = null
    ) {
        $dateInput = null;
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'date') {
                $dateInput = $input;
                break;
            }
        }

        if (empty($dateInput)) {
            throw new \RuntimeException('No date input for preferred day service found.');
        }

        /** @var OptionInterface[] $options */
        $options = array_map(
            function (IntervalOptionInterface $intervalOption) use ($storeId) {
                $optionLabel = $this->timezone->formatDate($intervalOption->getStart());
                $optionValue = (new \DateTime($intervalOption->getStart()))->format('Y-m-d');

                $dayOption = $this->optionFactory->create();
                $dayOption->setLabel($optionLabel);
                $dayOption->setValue($optionValue);

                return $dayOption;
            },
            $carrierService->getOptions()
        );

        if (empty($options)) {
            throw new \RuntimeException('No options for preferred day service available.');
        }

        $dateInput->setOptions($dateInput->getOptions() + $options);
    }

    /**
     * Process one shipping option
     *
     * @param ShippingOptionInterface $shippingOption
     * @param CarrierServiceInterface[] $carrierServices
     * @param int|null $storeId
     * @return ShippingOptionInterface|null
     */
    private function processShippingOption(
        ShippingOptionInterface $shippingOption,
        array $carrierServices,
        int $storeId = null
    ) {
        $serviceCode = $shippingOption->getCode();

        if (array_key_exists($serviceCode, $carrierServices) && !$carrierServices[$serviceCode]->isAvailable()) {
            // API returned additional information but service cannot be booked
            return null;
        }

        if (!array_key_exists($serviceCode, $carrierServices) && in_array(
            $serviceCode,
            self::SERVICES_WITH_OPTIONS,
            true
        )) {
            // API did not return any additional information but service requires options
            return null;
        }

        if ($serviceCode === Codes::CHECKOUT_SERVICE_PREFERRED_DAY) {
            // API returned option values for preferred day service, add them to the input element
            try {
                $this->addPreferredDayOptions($shippingOption, $carrierServices[$serviceCode], $storeId);

                return $shippingOption;
            } catch (\RuntimeException $exception) {
                return null;
            }
        }

        if ($serviceCode === self::SAME_DAY_DELIVERY) {
            // sameDayDelivery service is not (yet) supported
            return null;
        }

        return $shippingOption;
    }

    /**
     * Process given shipping options using the Parcel Management API result.
     *
     * Manipulation of shipping options includes:
     * - Set carrier service option values (i.e. possible dates and times for given postal code).
     * - Unset checkout services which require service option values when API result did not return any.
     * - Unset checkout services which are unavailable for given postal code.
     *
     * @param ShippingOptionInterface[] $optionsData
     * @param string $countryCode Destination country code
     * @param string $postalCode Destination postal code
     * @param int|null $storeId
     *
     * @return ShippingOptionInterface[]
     */
    public function process(
        array $optionsData,
        string $countryCode,
        string $postalCode,
        int $storeId = null
    ): array {
        if (empty(array_intersect(self::SERVICES_WITH_OPTIONS, array_keys($optionsData)))) {
            // Return early if no service that needs options from the API is available.
            return $optionsData;
        }

        $parcelManagementService = $this->serviceFactory->create(['storeId' => $storeId]);
        try {
            $startDate = $this->shipmentDate->getDate($storeId);
        } catch (\RuntimeException $exception) {
            // unable to determine start date, no valid data can be fetched
            return $optionsData;
        }

        try {
            $carrierServices = $parcelManagementService->getCarrierServices($postalCode, $startDate);

            // add service codes as array keys
            $carrierServiceCodes = array_map(
                static function (CarrierServiceInterface $carrierService) {
                    return $carrierService->getCode();
                },
                $carrierServices
            );

            $carrierServices = array_combine($carrierServiceCodes, $carrierServices);
        } catch (ServiceException $exception) {
            $carrierServices = [];
            $this->logger->error($exception->getMessage());
        }

        $shippingOptions = array_map(
            function (ShippingOptionInterface $shippingOption) use ($carrierServices, $storeId) {
                return $this->processShippingOption($shippingOption, $carrierServices, $storeId);
            },
            $optionsData
        );

        return array_filter($shippingOptions);
    }
}
