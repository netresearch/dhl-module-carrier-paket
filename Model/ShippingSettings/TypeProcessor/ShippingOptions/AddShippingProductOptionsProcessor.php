<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Util\ShippingProducts;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Config\ParcelProcessingConfigInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class AddShippingProductOptionsProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ShippingConfigInterface
     */
    private $shippingConfig;

    /**
     * @var ParcelProcessingConfigInterface
     */
    private $parcelConfig;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    public function __construct(
        ShippingConfigInterface $shippingConfig,
        ParcelProcessingConfigInterface $parcelConfig,
        ShippingProducts $shippingProducts,
        OptionInterfaceFactory $optionFactory
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->parcelConfig = $parcelConfig;
        $this->shippingProducts = $shippingProducts;
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
    public function process(
        string $carrierCode,
        array $shippingOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ShipmentInterface $shipment = null
    ): array {
        if ($carrierCode !== Paket::CARRIER_CODE) {
            // different carrier, nothing to modify.
            return $shippingOptions;
        }

        if (!$shipment) {
            // checkout scope, nothing to modify.
            return $shippingOptions;
        }

        $order = $shipment->getOrder();
        $optionCode = Codes::PACKAGE_OPTION_DETAILS;

        $packageDetails = $shippingOptions[$optionCode] ?? false;
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
        $paymentMethod = $order->getPayment()->getMethod();
        $isCodPayment = $this->parcelConfig->isCodPaymentMethod($paymentMethod, $storeId);
        $applicableProducts = array_map(
            function (array $regionProducts) use ($isCodPayment) {
                return array_filter(
                    $regionProducts,
                    function (string $product) use ($isCodPayment) {
                        return (!$isCodPayment || $product !== ShippingProducts::CODE_WARENPOST_NATIONAL);
                    }
                );
            },
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
