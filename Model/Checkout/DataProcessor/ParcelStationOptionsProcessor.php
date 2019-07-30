<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor;

use Dhl\Paket\Model\ProcessorInterface;
use Dhl\Paket\Webservice\PostFinderServiceFactory;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\AbstractProcessor;

/**
 * Class ParcelStationOptionsProcessor
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
        if (($countryId !== 'DE') || ($this->dhlConfig->getOriginCountry($scopeId) !== 'DE')) {
            return $optionsData;
        }

        foreach ($optionsData as $shippingOption) {
            if ($shippingOption->getCode() === ProcessorInterface::CHECKOUT_DELIVERY_PARCELSTATION) {
                $postFinderService = $this->postFinderServiceFactory->create(['storeId' => $scopeId]);
                foreach ($shippingOption->getInputs() as $input) {
                    if ($input->getCode() === 'id') {
                        $options = [];

                        $stations = $postFinderService->getParcelStations($countryId, $postalCode);
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
