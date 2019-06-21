<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\Service\StartDate;
use Dhl\Sdk\Paket\ParcelManagement\Api\Data\CarrierServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Service\CheckoutService\IntervalOption;
use Dhl\Sdk\Paket\ParcelManagement\Service\CheckoutService\TimeFrameOption;
use Dhl\Sdk\Paket\ParcelManagement\Service\ServiceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\InputInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\AbstractProcessor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PreferredDayTimeOptionsProcessor
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Max Melzer <max.melzer@netresearch.de>
 */
class PreferredDayTimeOptionsProcessor extends AbstractProcessor
{
    const PREFERRED_DAY = 'preferredDay';
    const PREFERRED_TIME = 'preferredTime';

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var StartDate
     */
    private $startDate;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PreferredDayTimeOptionsProcessor constructor.
     *
     * @param OptionInterfaceFactory $optionFactory
     * @param ServiceFactory $serviceFactory
     * @param ModuleConfig $moduleConfig
     * @param StartDate $startDate
     * @param TimezoneInterface $timeZone
     * @param LoggerInterface $logger
     */
    public function __construct(
        OptionInterfaceFactory $optionFactory,
        ServiceFactory $serviceFactory,
        ModuleConfig $moduleConfig,
        StartDate $startDate,
        TimezoneInterface $timeZone,
        LoggerInterface $logger
    ) {
        $this->optionFactory = $optionFactory;
        $this->serviceFactory = $serviceFactory;
        $this->moduleConfig = $moduleConfig;
        $this->startDate = $startDate;
        $this->timeZone = $timeZone;
        $this->logger = $logger;
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param string $countryId
     * @param string $postalCode
     * @param int|null $scopeId
     *
     * @return ShippingOptionInterface[]
     */
    public function processShippingOptions(
        array $optionsData,
        string $countryId,
        string $postalCode,
        int $scopeId = null
    ): array {

        $services = $this->getCheckoutServices($scopeId, $postalCode);

        foreach ($services as $service) {
            $serviceCode = $service->getCode();
            if (array_key_exists($serviceCode, $optionsData) && !$service->isAvailable()) {
                unset($optionsData[$serviceCode]);
            }

            if ($service->getCode() === self::PREFERRED_DAY && isset($optionsData[$service->getCode()])) {
                $dayOptions = $this->getPreferredDayOptions($service->getOptions());
                $this->setServiceInputOptions($optionsData, $service, $dayOptions);
            }
            if ($service->getCode() === self::PREFERRED_TIME && isset($optionsData[$service->getCode()])) {
                $timeOptions = $this->getPreferredTimeOptions($service->getOptions());
                $this->setServiceInputOptions($optionsData, $service, $timeOptions);
            }
        }

        return $optionsData;
    }

    /**
     * @param int $scopeId
     * @param string $postalCode
     *
     * @return array|CarrierServiceInterface[]
     */
    private function getCheckoutServices(int $scopeId, string $postalCode): array
    {
        $service = $this->serviceFactory->createCheckoutService(
            $applicationId = $this->moduleConfig->getAuthUsername(),
            $applicationToken = $this->moduleConfig->getAuthPassword(),
            $ekp = $this->moduleConfig->getEkp($scopeId),
            $logger = $this->logger,
            $sandbox = true
        );
        $startDate = $this->startDate->getStartDate()->format('Y-m-d');
        try {
            $carrierServices = $service->getCarrierServices($postalCode, $startDate);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $carrierServices = [];
        }

        return $carrierServices;
    }

    /**
     * @var IntervalOption[] $options
     * @return OptionInterface[]
     */
    private function getPreferredDayOptions(array $options): array
    {
        $dayOptions = [];
        /**
         * @var IntervalOption $option
         */
        foreach ($options as $option) {
            $optionLabel = $this->timeZone->formatDate($option->getStart());
            $optionValue = $this->timeZone->date($option->getStart())->format('Y-m-d');
            $dayOptions[] = $this->optionFactory->create([
                'label' => $optionLabel,
                'value' => $optionValue
            ]);
        }

        return $dayOptions;
    }

    /**
     * @param TimeFrameOption[] $options
     * @return OptionInterface[]
     */
    private function getPreferredTimeOptions(array $options): array
    {
        $timeOptions = [];
        foreach ($options as $option) {
            $timeOptions[] = $this->optionFactory->create([
                'label' => $option->getStart() . ' - ' . $option->getEnd(),
                'value' => str_replace(':', '', $option->getStart() . $option->getEnd())
             ]);
        }

        return $timeOptions;
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param CarrierServiceInterface $service
     * @param OptionInterface[] $serviceOptions
     */
    private function setServiceInputOptions(array $optionsData, $service, array $serviceOptions)
    {
        /** @var InputInterface[] $inputs */
        $inputs = $optionsData[$service->getCode()]->getInputs();
        if (!empty($inputs)) {
            foreach ($inputs as $input) {
                if (($input->getCode() === 'date' && $service->getCode() === self::PREFERRED_DAY) ||
                    ($input->getCode() === 'time' && $service->getCode() === self::PREFERRED_TIME)) {
                    $input->setOptions($input->getOptions() + $serviceOptions);
                }
            }
        }
    }
}
