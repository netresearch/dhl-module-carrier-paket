<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes as ServiceCodes;
use Dhl\Paket\Model\Util\ShippingProducts;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\OrderSelectionManager;

class AddShippingProductOptionsProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ShippingConfigInterface
     */
    private $shippingConfig;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

    /**
     * @var OrderSelectionManager
     */
    private $selectionManager;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    public function __construct(
        ShippingConfigInterface $shippingConfig,
        ShippingProducts $shippingProducts,
        OrderSelectionManager $selectionManager,
        OptionInterfaceFactory $optionFactory
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->shippingProducts = $shippingProducts;
        $this->selectionManager = $selectionManager;
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

    /**
     * Remove the products that are available for the route but do not support selected customer services.
     *
     * @param int $shippingAddressId
     * @param string[][] $shippingProducts
     * @return string[][]
     */
    private function filterByServiceSelection(int $shippingAddressId, array $shippingProducts): array
    {
        $selectedPaketOnlyServices = array_filter(
            $this->selectionManager->load($shippingAddressId),
            static function (SelectionInterface $selection) {
                return in_array(
                    $selection->getShippingOptionCode(),
                    [
                        Codes::SERVICE_OPTION_CASH_ON_DELIVERY,
                        ServiceCodes::SERVICE_OPTION_PREFERRED_DAY,
                        ServiceCodes::SERVICE_OPTION_NO_NEIGHBOR_DELIVERY,
                    ],
                    true
                );
            }
        );

        if (!empty($selectedPaketOnlyServices)) {
            $shippingProducts = array_map(
                function (array $regionProducts) {
                    return array_filter(
                        $regionProducts,
                        function (string $product) {
                            return ($product !== ShippingProducts::CODE_KLEINPAKET);
                        }
                    );
                },
                $shippingProducts
            );
        }

        return $shippingProducts;
    }

    /**
     * Add options and default value to the "productCode" input.
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

        if (!$shipment) {
            // checkout scope, nothing to modify.
            return $shippingOptions;
        }

        $packageDetails = $shippingOptions[Codes::PACKAGE_OPTION_DETAILS] ?? false;
        if (!$packageDetails instanceof ShippingOptionInterface) {
            // not the package details option, proceed.
            return $shippingOptions;
        }

        $productInput = $this->getOptionInput($packageDetails, Codes::PACKAGE_INPUT_PRODUCT_CODE);
        if (!$productInput instanceof InputInterface) {
            // product input not available, nothing to modify.
            return $shippingOptions;
        }

        $originCountry = $this->shippingConfig->getOriginCountry($storeId);

        // load products which apply to the current route
        $euCountries = $this->shippingConfig->getEuCountries($storeId);
        $applicableProducts = $this->shippingProducts->getShippingProducts(
            $originCountry,
            $countryCode,
            $euCountries
        );

        // remove products based on conditions other than route
        $applicableProducts = $this->filterByServiceSelection(
            (int) $shipment->getShippingAddressId(),
            $applicableProducts
        );

        // create input options from remaining products
        $options = [];
        foreach ($applicableProducts as $regionId => $regionProducts) {
            foreach ($regionProducts as $productCode) {
                $option = $this->optionFactory->create();
                $option->setValue($productCode);
                $option->setLabel($this->shippingProducts->getProductName($productCode));
                $options[]= $option;
            }
        }
        $productInput->setOptions($options);

        // set one of the input options as default, considering configured values
        $inputDefault = '';
        $defaultProducts = $this->shippingProducts->getDefaultProducts($originCountry);

        foreach ($defaultProducts as $regionId => $regionDefault) {
            if (in_array($regionDefault, $applicableProducts[$regionId] ?? [], true)) {
                // region default is applicable to current shipment, match!
                $inputDefault = $regionDefault;
                break;
            }
        }

        if (!$inputDefault) {
            // no defaults configured, use first available applicable product
            $inputDefault = current(current($applicableProducts));
        }
        $productInput->setDefaultValue((string)$inputDefault);
        return $shippingOptions;
    }
}
