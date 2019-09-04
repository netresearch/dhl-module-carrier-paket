<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor\ServiceOptions;

use Dhl\Paket\Model\ProcessorInterface;
use Dhl\Paket\Model\Service\StartDate;
use Dhl\Paket\Webservice\ParcelManagementServiceFactory;
use Dhl\Sdk\Paket\ParcelManagement\Api\Data\CarrierServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Api\Data\IntervalOptionInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\DataProcessor\ShippingOptionsProcessorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ParcelManagementOptionsProcessor
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Max Melzer <max.melzer@netresearch.de>
 */
class ParcelManagementOptionsProcessor implements ShippingOptionsProcessorInterface
{
    const SAME_DAY_DELIVERY = 'sameDayDelivery';

    const SERVICES_WITH_OPTIONS = [
        ProcessorInterface::CHECKOUT_SERVICE_PREFERRED_DAY,
        ProcessorInterface::CHECKOUT_SERVICE_PREFERRED_TIME,
        self::SAME_DAY_DELIVERY
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
     * @var StartDate
     */
    private $startDate;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ParcelManagementOptionsProcessor constructor.
     *
     * @param OptionInterfaceFactory $optionFactory
     * @param ParcelManagementServiceFactory $serviceFactory
     * @param StartDate $startDate
     * @param TimezoneInterface $timezone
     * @param LoggerInterface $logger
     */
    public function __construct(
        OptionInterfaceFactory $optionFactory,
        ParcelManagementServiceFactory $serviceFactory,
        StartDate $startDate,
        TimezoneInterface $timezone,
        LoggerInterface $logger
    ) {
        $this->optionFactory = $optionFactory;
        $this->serviceFactory = $serviceFactory;
        $this->startDate = $startDate;
        $this->timezone = $timezone;
        $this->logger = $logger;
    }

    /**
     * Retrieve day options from API response and add to form input.
     *
     * @param ShippingOptionInterface $shippingOption
     * @param CarrierServiceInterface $carrierService
     * @throws LocalizedException
     */
    private function addPreferredDayOptions(
        ShippingOptionInterface $shippingOption,
        CarrierServiceInterface $carrierService
    ) {
        $dateInput = null;
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'date') {
                $dateInput = $input;
                break;
            }
        }

        if (empty($dateInput)) {
            throw new LocalizedException(__('No date input for preferred day service found.'));
        }

        /** @var OptionInterface[] $options */
        $options = array_map(
            function (IntervalOptionInterface $intervalOption) {
                $optionLabel = $this->timezone->formatDate($intervalOption->getStart());
                $optionValue = $this->timezone->date($intervalOption->getStart())->format('Y-m-d');

                $dayOption = $this->optionFactory->create();
                $dayOption->setLabel($optionLabel);
                $dayOption->setValue($optionValue);

                return $dayOption;
            },
            $carrierService->getOptions()
        );

        if (empty($options)) {
            throw new LocalizedException(__('No options for preferred day service available.'));
        }

        $dateInput->setOptions($dateInput->getOptions() + $options);
    }

    /**
     * Retrieve time options from API response and add to form input.
     *
     * @param ShippingOptionInterface $shippingOption
     * @param CarrierServiceInterface $carrierService
     * @throws LocalizedException
     */
    private function addPreferredTimeOptions(
        ShippingOptionInterface $shippingOption,
        CarrierServiceInterface $carrierService
    ) {
        $timeInput = null;
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'time') {
                $timeInput = $input;
                break;
            }
        }

        if (empty($timeInput)) {
            throw new LocalizedException(__('No date input for preferred time service found.'));
        }

        /** @var OptionInterface[] $options */
        $options = array_map(
            function (IntervalOptionInterface $intervalOption) {
                $optionLabel = $intervalOption->getStart() . ' - ' . $intervalOption->getEnd();
                $optionValue = str_replace(':', '', $intervalOption->getStart() . $intervalOption->getEnd());

                $timeOption = $this->optionFactory->create();
                $timeOption->setLabel($optionLabel);
                $timeOption->setValue($optionValue);

                return $timeOption;
            },
            $carrierService->getOptions()
        );

        if (empty($options)) {
            throw new LocalizedException(__('No options for preferred time service available.'));
        }

        $timeInput->setOptions($timeInput->getOptions() + $options);
    }

    /**
     * Process one shipping option
     *
     * @param ShippingOptionInterface $shippingOption
     * @param CarrierServiceInterface[] $carrierServices
     * @return ShippingOptionInterface|null
     */
    private function processShippingOption(ShippingOptionInterface $shippingOption, array $carrierServices)
    {
        $serviceCode = $shippingOption->getCode();

        if (array_key_exists($serviceCode, $carrierServices) && !$carrierServices[$serviceCode]->isAvailable()) {
            // API returned additional information but service cannot be booked
            return null;
        }

        if (!array_key_exists($serviceCode, $carrierServices) && in_array($serviceCode, self::SERVICES_WITH_OPTIONS, true)) {
            // API did not return any additional information but service requires options
            return null;
        }

        if ($serviceCode === ProcessorInterface::CHECKOUT_SERVICE_PREFERRED_DAY) {
            // API returned option values for preferred day service, add them to the input element
            try {
                $this->addPreferredDayOptions($shippingOption, $carrierServices[$serviceCode]);
                return $shippingOption;
            } catch (LocalizedException $exception) {
                return null;
            }
        }

        if ($serviceCode === ProcessorInterface::CHECKOUT_SERVICE_PREFERRED_TIME) {
            // API returned option values for preferred time service, add them to the input element
            try {
                $this->addPreferredTimeOptions($shippingOption, $carrierServices[$serviceCode]);
                return $shippingOption;
            } catch (LocalizedException $exception) {
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
        if (empty(array_intersect_key(
            self::SERVICES_WITH_OPTIONS,
            array_keys($optionsData)
        ))) {
            // Return early if no service that needs options from the API is available.
            return $optionsData;
        }

        $parcelManagementService = $this->serviceFactory->create(['storeId' => $storeId]);
        $startDate = $this->startDate->getStartDate()->format('Y-m-d');
        $carrierServices = [];

        try {
            $carrierServices = $parcelManagementService->getCarrierServices($postalCode, $startDate);

            // add service codes as array keys
            $carrierServiceCodes = array_map(
                function (CarrierServiceInterface $carrierService) {
                    return $carrierService->getCode();
                },
                $carrierServices
            );

            $carrierServices = array_combine($carrierServiceCodes, $carrierServices);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        $shippingOptions = array_map(
            function (ShippingOptionInterface $shippingOption) use ($carrierServices) {
                return $this->processShippingOption($shippingOption, $carrierServices);
            },
            $optionsData
        );

        return array_filter($shippingOptions);
    }
}
