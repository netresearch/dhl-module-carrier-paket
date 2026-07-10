<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ItemShippingOptions;

use Dhl\Paket\Model\Util\UsCustomsTerritory;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ItemShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

/**
 * Replace the generic HS Code tooltip with HTSUS guidance for US customs territory destinations.
 *
 * Merchants must determine the 10-digit, US-specific HTSUS code themselves — Deutsche Post
 * provides no lookup service — so the input links the official sources. The tooltip is rendered
 * as HTML by the packaging popup (Magento's ui/form/element/helper/tooltip template), links work.
 * Other destinations keep the generic tooltip configured in shipping-core's shipping_settings.xml.
 */
class HsCodeTooltipProcessor implements ItemShippingOptionsProcessorInterface
{
    /**
     * Set the HTSUS help text on hsCode inputs for US/Puerto-Rico destinations.
     *
     * @param string $carrierCode The code for the carrier.
     * @param array $itemOptions The list of item options to be processed.
     * @param int $storeId The ID of the store.
     * @param string $countryCode The destination country code.
     * @param string $postalCode The destination postal code.
     * @param ShipmentInterface|null $shipment The shipment instance, optional.
     * @return array The processed item options with updated tooltip.
     */
    #[\Override]
    public function process(
        string $carrierCode,
        array $itemOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ?ShipmentInterface $shipment = null
    ): array {
        if (!in_array($countryCode, UsCustomsTerritory::COUNTRY_CODES, true)) {
            return $itemOptions;
        }

        foreach ($itemOptions as $itemOption) {
            $shippingOptions = $itemOption->getShippingOptions();
            $itemCustomsOption = $shippingOptions[Codes::ITEM_OPTION_CUSTOMS] ?? null;
            if (!$itemCustomsOption) {
                continue;
            }

            foreach ($itemCustomsOption->getInputs() as $input) {
                if ($input->getCode() !== Codes::ITEM_INPUT_HS_CODE) {
                    continue;
                }

                $input->setTooltip(
                    (string) __(
                        'Shipments to the USA and Puerto Rico require the 10-digit, US-specific HTSUS code per item. Look it up in the official %1Harmonized Tariff Schedule%2. See also %3dhl.de/us-versand%4.',
                        '<a href="https://hts.usitc.gov/" target="_blank" rel="noopener">',
                        '</a>',
                        '<a href="https://www.dhl.de/us-versand" target="_blank" rel="noopener">',
                        '</a>'
                    )
                );
            }
        }

        return $itemOptions;
    }
}
