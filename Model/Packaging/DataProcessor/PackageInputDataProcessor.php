<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Packaging\DataProcessor;

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
     * @var CommentInterfaceFactory
     */
    private $commentFactory;

    /**
     * PackageInputDataProcessor constructor.
     * @param CommentInterfaceFactory $commentFactory
     */
    public function __construct(CommentInterfaceFactory $commentFactory)
    {
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
            if ($input->getCode() === 'additionalFee') {
                $currency = $shipment->getStore()->getBaseCurrency();
                $currencySymbol = $currency->getCurrencySymbol() ?: $currency->getCode();
                $comment = $this->commentFactory->create();
                $comment->setContent($currencySymbol);
                $input->setComment($comment);
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

        foreach ($optionsData as $optionGroup) {
            $this->processInputs($optionGroup, $shipment);
        }

        return $optionsData;
    }
}
