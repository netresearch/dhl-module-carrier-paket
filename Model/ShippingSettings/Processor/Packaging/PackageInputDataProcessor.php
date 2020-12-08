<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Packaging;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CommentInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Packaging\ShippingOptionsProcessorInterface;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;

class PackageInputDataProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ConfigInterface
     */
    private $dhlConfig;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

    /**
     * @var CommentInterfaceFactory
     */
    private $commentFactory;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    public function __construct(
        ConfigInterface $dhlConfig,
        ShippingProducts $shippingProducts,
        CommentInterfaceFactory $commentFactory,
        OptionInterfaceFactory $optionFactory
    ) {
        $this->dhlConfig = $dhlConfig;
        $this->shippingProducts = $shippingProducts;
        $this->commentFactory = $commentFactory;
        $this->optionFactory = $optionFactory;
    }

    /**
     * Set options and values to inputs on package level.
     *
     * @param ShippingOptionInterface $shippingOption
     * @param ShipmentInterface $shipment
     */
    private function processInputs(ShippingOptionInterface $shippingOption, ShipmentInterface $shipment)
    {
        foreach ($shippingOption->getInputs() as $input) {
            switch ($input->getCode()) {
                case Codes::PACKAGING_INPUT_PRODUCT_CODE:
                    $storeId = $shipment->getStoreId();

                    /** @var \Magento\Sales\Model\Order $order */
                    $order = $shipment->getOrder();

                    $originCountry = $this->dhlConfig->getOriginCountry($storeId);
                    $destinationCountry = $order->getShippingAddress()->getCountryId();

                    // load products which apply to the current route
                    $euCountries = $this->dhlConfig->getEuCountries($storeId);
                    $applicableProducts = $this->shippingProducts->getShippingProducts(
                        $originCountry,
                        $destinationCountry,
                        $euCountries
                    );

                    // remove products based on conditions other that route
                    $isCodPayment = $this->dhlConfig->isCodPaymentMethod($order->getPayment()->getMethod(), $storeId);
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

                    $input->setOptions($options);

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
                    $input->setDefaultValue((string)$inputDefault);
                    break;
            }
        }
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

        foreach ($optionsData as $optionGroup) {
            $this->processInputs($optionGroup, $shipment);
        }

        return $optionsData;
    }
}
