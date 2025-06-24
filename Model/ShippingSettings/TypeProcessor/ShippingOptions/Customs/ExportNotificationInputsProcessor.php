<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions\Customs;

use Dhl\Paket\Model\Carrier\Paket;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class ExportNotificationInputsProcessor implements ShippingOptionsProcessorInterface
{

    /**
     * Retrieves the input associated with a specific code from the given shipping service option.
     *
     * @param ShippingOptionInterface $serviceOption The shipping service option containing inputs.
     * @return InputInterface|null The input matching the specified code, or null if not found.
     */
    private function getOptionInput(ShippingOptionInterface $serviceOption): ?InputInterface
    {
        foreach ($serviceOption->getInputs() as $input) {
            if ($input->getCode() === 'electronicExportNotification') {
                return $input;
            }
        }

        return null;
    }

    /**
     * Enables the electronic export notification input for the given shipping option and order.
     *
     * @param ShippingOptionInterface $customsOption The shipping option associated with customs configuration.
     * @param OrderInterface $order The order whose grand total determines if the notification input is enabled.
     * @return void
     */
    private function enableElectronicExportNotificationInput(
        ShippingOptionInterface $customsOption,
        OrderInterface $order
    ): void {
        $input = $this->getOptionInput($customsOption);
        if (!$input instanceof InputInterface) {
            return;
        }

        if ($order->getSubtotal() >= 1000) {
            $input->setDefaultValue('1');
        }
    }

    private function addToolTipContent(
        ShippingOptionInterface $customsOption,
        OrderInterface $order
    ): void {
        $input = $this->getOptionInput($customsOption);
        if (!$input instanceof InputInterface) {
            return;
        }

        $input->setTooltip(
            __('<b>Electronic Export Notification</b>')->render()
            . '<br><br>'
            . __("Shipment with export declaration (This option is automatically selected when the goods value of the shipment exceeds 1000 EUR. Please only select this option yourself for goods valued under 1000 EUR if the shipment has been electronically declared for export to customs via ATLAS, IAA PLUS and it is ensured that an export accompanying document is attached to the shipment.)")->render()
            . '<br><br>'
            . __("<b>Export Declaration Notice</b>")->render()
            . '<br><br>'
            . __('<b>Important!</b>')->render()
            . '<br><br>'
            . __("Please make sure to place the export accompanying document together with the other customs documents in the customs document pouch and affix the sticker <em>Attention Export Declaration</em> - Material No. 915-830-100 near the recipient's address. If the export accompanying document is missing, your shipment cannot be exported and will be returned to you.")->render()

        );
    }

    /**
     * Processes the shipping options for a given carrie and applies modifications if necessary.
     *
     * @param string $carrierCode The carrier code to be checked.
     * @param array $shippingOptions The available shipping options to be processed.
     * @param int $storeId The store ID context for the shipping options.
     * @param string $countryCode The destination country code.
     * @param string $postalCode The destination postal code.
     * @param ShipmentInterface|null $shipment The shipment instance for which the shipping options are being processed, or null if in checkout scope.
     * @return array The modified or original array of shipping options based on the carrier and shipment context.
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

        $optionCode = Codes::PACKAGE_OPTION_CUSTOMS;
        $customsOption = $shippingOptions[$optionCode] ?? false;
        if (!$customsOption instanceof ShippingOptionInterface) {
            // not the package customs option, proceed.
            return $shippingOptions;
        }

        $order = $shipment->getOrder();
        $this->enableElectronicExportNotificationInput($customsOption, $order);
        $this->addToolTipContent($customsOption, $order);

        return $shippingOptions;
    }
}
