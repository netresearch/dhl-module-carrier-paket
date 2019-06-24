<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor;

use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\AbstractProcessor;

/**
 * Class ParcelStationOptionsProcessor
 *
 * @todo(nr): the processor currently adds fake data. use postfinder api.
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Sebastian Ertner <sebastian.ertner@netresearch.de>
 */
class ParcelStationOptionsProcessor extends AbstractProcessor
{
    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * ParcelStationOptionsProcessor constructor.
     *
     * @param OptionInterfaceFactory $optionFactory
     */
    public function __construct(OptionInterfaceFactory $optionFactory)
    {
        $this->optionFactory = $optionFactory;
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
        foreach ($optionsData as $shippingOption) {
            if ($shippingOption->getCode() === 'parcelstation') {
                foreach ($shippingOption->getInputs() as $input) {
                    if ($input->getCode() === 'id') {
                        $input->setOptions($input->getOptions() + $this->getParcelStationId());
                    }
                }
            }
        }

        return $optionsData;
    }

    /**
     * @return OptionInterface[]
     */
    private function getParcelStationId(): array
    {
        $options = [];
        for ($i = 1; $i < 4; $i++) {
            $option = $this->optionFactory->create();
            $option->setValue("test{$i}");
            $option->setLabel("Parcel Station {$i}");
            $options[]= $option;
        }

        return $options;
    }
}
