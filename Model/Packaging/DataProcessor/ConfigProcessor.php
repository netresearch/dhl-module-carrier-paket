<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Packaging\DataProcessor;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Packaging\AbstractProcessor;
use Dhl\ShippingCore\Model\Packaging\PackagingDataProvider;
use Magento\Sales\Model\Order\Shipment;

/**
 * Class ConfigProcessor
 *
 * @package Dhl\Paket\Model\Packaging\DataProcessor
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 */
class ConfigProcessor extends AbstractProcessor
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * ConfigProcessor constructor.
     *
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(ModuleConfig $moduleConfig)
    {
        $this->moduleConfig = $moduleConfig;
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

        foreach ($optionsData as $shippingOption) {
            if ($shippingOption->getCode() === 'printOnlyIfCodeable') {
                $this->setPrintOnlyIfCodeable($shipment, $shippingOption);
            } elseif ($shippingOption->getCode() === 'visualCheckOfAge') {
                $this->setVisualCheckOfAge($shipment, $shippingOption);
            } elseif ($shippingOption->getCode() === 'returnShipment') {
                $this->setReturnShipment($shipment, $shippingOption);
            } elseif ($shippingOption->getCode() === 'additionalInsurance') {
                $this->setAdditionalInsurance($shipment, $shippingOption);
            } elseif ($shippingOption->getCode() === 'bulkyGoods') {
                $this->setBulkyGoods($shipment, $shippingOption);
            }
        }

        return $optionsData;
    }

    /**
     * @param Shipment $shipment
     * @param ShippingOptionInterface $shippingOption
     */
    private function setPrintOnlyIfCodeable(Shipment $shipment, ShippingOptionInterface $shippingOption)
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'enabled') {
                $input->setDefaultValue((string) $this->moduleConfig->printOnlyIfCodeable($shipment->getStoreId()));
            }
        }
    }

    /**
     * @param Shipment $shipment
     * @param ShippingOptionInterface $shippingOption
     */
    private function setVisualCheckOfAge(Shipment $shipment, ShippingOptionInterface $shippingOption)
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'enabled') {
                $input->setDefaultValue($this->moduleConfig->visualCheckOfAge($shipment->getStoreId()));
            }
        }
    }

    /**
     * @param Shipment $shipment
     * @param ShippingOptionInterface $shippingOption
     */
    private function setReturnShipment(Shipment $shipment, ShippingOptionInterface $shippingOption)
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'enabled') {
                $input->setDefaultValue((string) $this->moduleConfig->returnShipment($shipment->getStoreId()));
            }
        }
    }

    /**
     * @param Shipment $shipment
     * @param ShippingOptionInterface $shippingOption
     */
    private function setAdditionalInsurance(Shipment $shipment, ShippingOptionInterface $shippingOption)
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'enabled') {
                $input->setDefaultValue((string) $this->moduleConfig->additionalInsurance($shipment->getStoreId()));
            }
        }
    }

    /**
     * @param Shipment $shipment
     * @param ShippingOptionInterface $shippingOption
     */
    private function setBulkyGoods(Shipment $shipment, ShippingOptionInterface $shippingOption)
    {
        foreach ($shippingOption->getInputs() as $input) {
            if ($input->getCode() === 'enabled') {
                $input->setDefaultValue((string) $this->moduleConfig->bulkyGoods($shipment->getStoreId()));
            }
        }
    }
}
