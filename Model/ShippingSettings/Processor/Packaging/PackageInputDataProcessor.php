<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Packaging;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CommentInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Packaging\ShippingOptionsProcessorInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * Class PackageInputDataProcessor
 *
 * @package Dhl\Paket\Model\Packaging\DataProcessor
 * @author Sebastian Ertner <sebastian.ertner@netresearch.de>
 */
class PackageInputDataProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

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
     * PackageInputDataProcessor constructor.
     *
     * @param ModuleConfig $config
     * @param ConfigInterface $dhlConfig
     * @param ShippingProducts $shippingProducts
     * @param CommentInterfaceFactory $commentFactory
     */
    public function __construct(
        ModuleConfig $config,
        ConfigInterface $dhlConfig,
        ShippingProducts $shippingProducts,
        CommentInterfaceFactory $commentFactory
    ) {
        $this->config = $config;
        $this->dhlConfig = $dhlConfig;
        $this->shippingProducts = $shippingProducts;
        $this->commentFactory = $commentFactory;
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
                case 'productCode':
                    $storeId = $shipment->getStoreId();

                    /** @var \Magento\Sales\Model\Order $order */
                    $order = $shipment->getOrder();
                    $originCountry = $this->dhlConfig->getOriginCountry($storeId);
                    $destinationCountry = $order->getShippingAddress()->getCountryId();
                    $euCountries = $this->dhlConfig->getEuCountries($storeId);

                    $applicableProducts = $this->shippingProducts->getShippingProducts(
                        $originCountry,
                        $destinationCountry,
                        $euCountries
                    );

                    $options = [];
                    foreach ($applicableProducts as $regionId => $regionProducts) {
                        foreach ($regionProducts as $productCode) {
                            $options[]= [
                                'value' => $productCode,
                                'label' => $this->shippingProducts->getProductName($productCode),
                            ];
                        }
                    }
                    $input->setOptions($options);

                    $inputDefault = '';
                    $defaultProducts = $this->shippingProducts->getDefaultProducts($originCountry);
                    foreach ($defaultProducts as $regionId => $regionDefault) {
                        if (!isset($applicableProducts[$regionId])) {
                            continue;
                        }

                        if (in_array($regionDefault, $applicableProducts[$regionId], true)) {
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

                case 'additionalFee':
                    /** @var \Magento\Store\Model\Store $store */
                    $store = $shipment->getStore();
                    $currency = $store->getBaseCurrency();
                    $currencySymbol = $currency->getCurrencySymbol() ?: $currency->getCode();
                    $comment = $this->commentFactory->create();
                    $comment->setContent($currencySymbol);
                    $input->setComment($comment);
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
