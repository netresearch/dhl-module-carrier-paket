<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Packaging\DataProcessor;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Packaging\AbstractProcessor;
use Dhl\ShippingCore\Model\Packaging\PackagingDataProvider;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order\Shipment;

/**
 * Class ServiceInputDataProcessor
 *
 * @package Dhl\Paket\Model\Packaging\DataProcessor
 * @author Sebastian Ertner <sebastian.ertner@netresearch.de>
 */
class ServiceInputDataProcessor extends AbstractProcessor
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * ServiceInputDataProcessor constructor.
     * @param TimezoneInterface $timezone
     */
    public function __construct(TimezoneInterface $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Infer radio button label from selection value.
     *
     * @param ShippingOptionInterface $shippingOption
     */
    private function processParcelStationInputs(ShippingOptionInterface $shippingOption)
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'id') {
                list($stationId, $countryId, $postalCode, $city) = explode('|', $input->getDefaultValue());
                $input->setLabel(
                    sprintf(
                        'Packstation %s, %s %s %s',
                        $stationId,
                        $countryId,
                        $postalCode,
                        $city
                    )
                );
            }
        }
    }

    /**
     * Infer radio button label from selection value.
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

                $input->setLabel($dateFormatted);
            }
        }
    }

    /**
     * Infer radio button label from selection value.
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

                $input->setLabel($startTime . ' - ' . $endTime);
            }
        }
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param Shipment $shipment
     * @param string $optionGroupName
     *
     * @return ShippingOptionInterface[]
     */
    public function processShippingOptions(array $optionsData, Shipment $shipment, string $optionGroupName): array
    {
        if ($optionGroupName !== PackagingDataProvider::GROUP_SERVICE) {
            return $optionsData;
        }

        $carrierCode = strtok((string) $shipment->getOrder()->getShippingMethod(), '_');
        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $optionsData;
        }

        foreach ($optionsData as $optionGroup) {
            switch ($optionGroup->getCode()) {
                case 'parcelstation':
                    $this->processParcelStationInputs($optionGroup);
                    break;
                case 'preferredDay':
                    $this->processPreferredDayInputs($optionGroup);
                    break;
                case 'preferredTime':
                    $this->processPreferredTimeInputs($optionGroup);
                    break;
            }
        }

        return $optionsData;
    }
}
