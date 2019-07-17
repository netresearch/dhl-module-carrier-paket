<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Packaging\DataProcessor;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\Adminhtml\System\Config\Source\ShippingProduct;
use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\CommentInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Packaging\AbstractProcessor;
use Dhl\ShippingCore\Model\Packaging\PackagingDataProvider;
use Magento\Sales\Model\Order\Shipment;

/**
 * Class PackageInputDataProcessor
 *
 * @package Dhl\Paket\Model\Packaging\DataProcessor
 * @author Sebastian Ertner <sebastian.ertner@netresearch.de>
 */
class PackageInputDataProcessor extends AbstractProcessor
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
     * @var ShippingProduct
     */
    private $productSource;

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
     * @param ShippingProduct $productSource
     * @param ShippingProducts $shippingProducts
     * @param CommentInterfaceFactory $commentFactory
     */
    public function __construct(
        ModuleConfig $config,
        ConfigInterface $dhlConfig,
        ShippingProduct $productSource,
        ShippingProducts $shippingProducts,
        CommentInterfaceFactory $commentFactory
    ) {
        $this->config = $config;
        $this->dhlConfig = $dhlConfig;
        $this->productSource = $productSource;
        $this->shippingProducts = $shippingProducts;
        $this->commentFactory = $commentFactory;
    }

    /**
     * Set options and values to inputs on package level.
     *
     * @param ShippingOptionInterface $shippingOption
     * @param Shipment $shipment
     */
    private function processInputs(ShippingOptionInterface $shippingOption, Shipment $shipment)
    {
        foreach ($shippingOption->getInputs() as $input) {
            switch ($input->getCode()) {
                case 'productCode':
                    $storeId = $shipment->getStoreId();

                    $originCountry = $this->dhlConfig->getOriginCountry($storeId);
                    $destinationCountry = $shipment->getOrder()->getShippingAddress()->getCountryId();
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

                    $default = '';
                    foreach ($this->config->getShippingProductDefaults($storeId) as $regionId => $regionDefault) {
                        if (!isset($applicableProducts[$regionId])) {
                            continue;
                        }

                        if (in_array($regionDefault, $applicableProducts[$regionId])) {
                            $default = $regionDefault;
                            break;
                        }
                    }

                    if (!$default) {
                        // no defaults configured, use first available applicable product
                        $default = current(current($applicableProducts));
                    }
                    $input->setDefaultValue($default);
                    break;

                case 'additionalFee':
                    $currency = $shipment->getStore()->getBaseCurrency();
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
     * @param Shipment $shipment
     * @param string $optionGroupName
     *
     * @return ShippingOptionInterface[]
     */
    public function processShippingOptions(array $optionsData, Shipment $shipment, string $optionGroupName): array
    {
        if ($optionGroupName !== PackagingDataProvider::GROUP_PACKAGE) {
            return $optionsData;
        }

        $carrierCode = strtok((string) $shipment->getOrder()->getShippingMethod(), '_');
        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $optionsData;
        }

        foreach ($optionsData as $optionGroup) {
            $this->processInputs($optionGroup, $shipment);
        }

        return $optionsData;
    }
}
