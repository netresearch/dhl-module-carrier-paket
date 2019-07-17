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

        foreach ($optionsData as $shippingOption) {
            if ($shippingOption->getCode() !== 'parcelstation') {
                // not interested in anything else but parcel station service
                continue;
            }

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

        return $optionsData;
    }
}
