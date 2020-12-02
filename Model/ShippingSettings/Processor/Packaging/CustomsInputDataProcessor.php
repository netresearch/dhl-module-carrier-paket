<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\Processor\Packaging;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CommentInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Packaging\ShippingOptionsProcessorInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

class CustomsInputDataProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var CommentInterfaceFactory
     */
    private $commentFactory;

    public function __construct(ModuleConfig $config, CommentInterfaceFactory $commentFactory)
    {
        $this->config = $config;
        $this->commentFactory = $commentFactory;
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param ShipmentInterface $shipment
     *
     * @return ShippingOptionInterface[]
     */
    public function process(array $optionsData, ShipmentInterface $shipment): array
    {
        $order = $shipment->getOrder();
        $carrierCode = strtok((string) $order->getShippingMethod(), '_');

        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $optionsData;
        }

        foreach ($optionsData as $optionGroup) {
            foreach ($optionGroup->getInputs() as $input) {
                if ($input->getCode() === 'additionalFee') {
                    $store = $shipment->getStore();
                    $currency = $store->getBaseCurrency();
                    $currencySymbol = $currency->getCurrencySymbol() ?: $currency->getCode();
                    $comment = $this->commentFactory->create();
                    $comment->setContent($currencySymbol);
                    $input->setComment($comment);

                    $baseShippingIncTax = (float) $order->getBaseShippingInclTax();
                    $baseShippingRefunded = (float) $order->getBaseShippingRefunded();
                    $baseShippingTaxRefunded = (float) $order->getBaseShippingTaxRefunded();
                    $shippingCost = $baseShippingIncTax -$baseShippingRefunded -$baseShippingTaxRefunded;
                    $input->setDefaultValue((string) $shippingCost);
                } elseif ($input->getCode() === 'sendersCustomsReference') {
                    $destinationCountry = $order->getShippingAddress()->getCountryId();
                    $referenceNumbers = $this->config->getCustomsReferenceNumbers($shipment->getStoreId());
                    $value = $referenceNumbers[$destinationCountry] ?? '';
                    $input->setDefaultValue($value);
                }
            }
        }

        return $optionsData;
    }
}
