<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor\ServiceOptions;

use Dhl\Paket\Model\ProcessorInterface;
use Dhl\Paket\Webservice\PostFinderServiceFactory;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\DataProcessor\ShippingOptionsProcessorInterface;

/**
 * Class ParcelStationOptionsProcessor
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Sebastian Ertner <sebastian.ertner@netresearch.de>
 */
class ParcelStationOptionsProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * @var PostFinderServiceFactory
     */
    private $postFinderServiceFactory;

    /**
     * @var ConfigInterface
     */
    private $dhlConfig;

    /**
     * ParcelStationOptionsProcessor constructor.
     *
     * @param OptionInterfaceFactory $optionFactory
     * @param PostFinderServiceFactory $postFinderServiceFactory
     * @param ConfigInterface $dhlConfig
     */
    public function __construct(
        OptionInterfaceFactory $optionFactory,
        PostFinderServiceFactory $postFinderServiceFactory,
        ConfigInterface $dhlConfig
    ) {
        $this->optionFactory = $optionFactory;
        $this->postFinderServiceFactory = $postFinderServiceFactory;
        $this->dhlConfig = $dhlConfig;
    }

    /**
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
        if (($countryCode !== 'DE') || ($this->dhlConfig->getOriginCountry($storeId) !== 'DE')) {
            return $optionsData;
        }

        foreach ($optionsData as $shippingOption) {
            if ($shippingOption->getCode() === ProcessorInterface::CHECKOUT_DELIVERY_PARCELSTATION) {
                $postFinderService = $this->postFinderServiceFactory->create(['storeId' => $storeId]);
                foreach ($shippingOption->getInputs() as $input) {
                    if ($input->getCode() === 'id') {
                        $options = [];

                        $stations = $postFinderService->getParcelStations($countryCode, $postalCode);
                        foreach ($stations as $station) {
                            $street = $station['street'];
                            if ($station['streetNo']) {
                                $street .= ' ' . $station['streetNo'];
                            }

                            $value = sprintf(
                                '%s|%s|%s|%s',
                                $station['parcelStationNumber'],
                                $station['country'],
                                $station['zip'],
                                $station['city']
                            );
                            $label = sprintf(
                                'Packstation %s, %s, %s %s',
                                $station['parcelStationNumber'],
                                $street,
                                $station['zip'],
                                $station['city']
                            );
                            $option = $this->optionFactory->create();
                            $option->setValue($value);
                            $option->setLabel($label);
                            $options[] = $option;
                        }

                        $input->setOptions($input->getOptions() + $options);
                    }
                }
            }
        }

        return $optionsData;
    }
}
