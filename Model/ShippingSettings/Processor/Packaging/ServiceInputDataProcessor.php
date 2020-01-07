<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Packaging;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\AssignedSelectionInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Packaging\ShippingOptionsProcessorInterface;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\OrderSelectionRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * Class ServiceInputDataProcessor
 *
 * @package Dhl\Paket\Model\Packaging\DataProcessor
 * @author Sebastian Ertner <sebastian.ertner@netresearch.de>
 */
class ServiceInputDataProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var OrderSelectionRepository
     */
    private $selectionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * List of available customer services in checkout.
     *
     * @var string[]
     */
    private static $availableCustomerServices = [
        Codes::CHECKOUT_SERVICE_PREFERRED_DAY,
        Codes::CHECKOUT_SERVICE_PREFERRED_TIME,
        Codes::CHECKOUT_SERVICE_PREFERRED_LOCATION,
        Codes::CHECKOUT_SERVICE_PREFERRED_NEIGHBOUR,
        Codes::CHECKOUT_SERVICE_PARCELSHOP_FINDER,
    ];

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * ServiceInputDataProcessor constructor.
     *
     * @param TimezoneInterface $timezone
     * @param OrderSelectionRepository $selectionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OptionInterfaceFactory $optionFactory
     */
    public function __construct(
        TimezoneInterface $timezone,
        OrderSelectionRepository $selectionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OptionInterfaceFactory $optionFactory
    ) {
        $this->timezone = $timezone;
        $this->selectionRepository = $selectionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->optionFactory = $optionFactory;
    }

    /**
     * Infer radio button label from selection value.
     *
     * This must be done manually since the proper value labels are
     * only retrieved from the API during checkout and that data is
     * not persisted.
     *
     * @param ShippingOptionInterface $shippingOption
     */
    private function processPreferredDayInputs(ShippingOptionInterface $shippingOption)
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'date') {
                $dateFormatted = $this->timezone->formatDate(
                    $input->getDefaultValue(),
                    \IntlDateFormatter::MEDIUM
                );
                /** @var OptionInterface $option */
                $option = $this->optionFactory->create();
                $option->setValue($input->getDefaultValue());
                $option->setLabel($dateFormatted);
                $input->setOptions([$option]);
            }
        }
    }

    /**
     * Infer radio button label from selection value.
     *
     * This must be done manually since the proper value labels are
     * only retrieved from the API during checkout and that data is
     * not persisted.
     *
     * @param ShippingOptionInterface $shippingOption
     */
    private function processPreferredTimeInputs(ShippingOptionInterface $shippingOption)
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'time') {
                $timeRange = str_split($input->getDefaultValue(), 4);
                $startTime = implode(':', str_split($timeRange[0], 2));
                $endTime = implode(':', str_split($timeRange[1], 2));

                /** @var OptionInterface $option */
                $option = $this->optionFactory->create();
                $option->setValue($input->getDefaultValue());
                $option->setLabel($startTime . ' - ' . $endTime);
                $input->setOptions([$option]);
            }
        }
    }

    /**
     * Filters all not selected customer services out of the options data array
     * so they are never rendered in the Packaging Popup.
     *
     * @param AssignedSelectionInterface[] $selections
     * @param ShippingOptionInterface[] $optionsData
     *
     * @return ShippingOptionInterface[]
     */
    private function filterNotSelectedServices(array $selections, array $optionsData): array
    {
        $selectedServices = [];
        foreach ($selections as $selection) {
            $selectedServices[] = $selection->getShippingOptionCode();
        }

        $notSelectedServices = array_diff(self::$availableCustomerServices, array_unique($selectedServices));

        foreach ($optionsData as $optionCode => $shippingOption) {
            if (in_array($shippingOption->getCode(), $notSelectedServices, true)) {
                unset($optionsData[$optionCode]);
            }
        }

        return $optionsData;
    }

    /**
     * @param int $orderAddressId
     * @return AssignedSelectionInterface[]
     */
    private function loadSelections(int $orderAddressId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                AssignedSelectionInterface::PARENT_ID,
                $orderAddressId
            )->create();

        return $this->selectionRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Set all 'enabled' inputs' default value to true if their parent shipping option was in any way selected.
     * This needs to be done manually because the 'enabled' inputs are never available in checkout.
     *
     * @param AssignedSelectionInterface[] $selections
     * @param ShippingOptionInterface[] $optionsData
     *
     * @return ShippingOptionInterface[]
     */
    private function setEnabledInputValues(array $selections, array $optionsData): array
    {
        foreach ($selections as $selection) {
            foreach ($optionsData as $shippingOption) {
                if ($shippingOption->getCode() !== $selection->getShippingOptionCode()) {
                    continue;
                }

                foreach ($shippingOption->getInputs() as $input) {
                    if ($input->getCode() === 'enabled') {
                        $input->setDefaultValue('1');
                    }
                }
            }
        }

        return $optionsData;
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param ShipmentInterface $shipment
     *
     * @return ShippingOptionInterface[]
     */
    public function process(array $optionsData, ShipmentInterface $shipment): array
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();
        $carrierCode = strtok((string) $order->getShippingMethod(), '_');

        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $optionsData;
        }

        $addressId = (int) $shipment->getShippingAddressId();
        $selections = $this->loadSelections($addressId);

        $optionsData = $this->filterNotSelectedServices($selections, $optionsData);
        $optionsData = $this->setEnabledInputValues($selections, $optionsData);

        foreach ($optionsData as $optionGroup) {
            switch ($optionGroup->getCode()) {
                case Codes::CHECKOUT_SERVICE_PREFERRED_DAY:
                    $this->processPreferredDayInputs($optionGroup);
                    break;
                case Codes::CHECKOUT_SERVICE_PREFERRED_TIME:
                    $this->processPreferredTimeInputs($optionGroup);
                    break;
            }
        }

        return $optionsData;
    }
}
