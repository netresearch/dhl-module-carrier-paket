<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use Dhl\Paket\Model\AdditionalFee\AdditionalFeeProvider;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Pricing\Helper\Data as CurrencyConverter;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CommentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\InputInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;

class AdditionalFeeProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var AdditionalFeeProvider
     */
    private $feeProvider;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    public function __construct(AdditionalFeeProvider $feeProvider, CurrencyConverter $currencyConverter)
    {
        $this->feeProvider = $feeProvider;
        $this->currencyConverter = $currencyConverter;
    }

    private function updateInputComment(InputInterface $input, float $amount, int $storeId): void
    {
        $comment = $input->getComment();
        $text = ($comment instanceof CommentInterface) ? $comment->getContent() : '';

        if (empty($amount) && (str_contains($text, '$1'))) {
            // no amount given, clear template
            $comment->setContent('');
            return;
        }

        $formattedAmount = $this->currencyConverter->currencyByStore($amount, $storeId, true, false);
        $translation = __($comment->getContent())->render();
        $comment->setContent(str_replace('$1', $formattedAmount, $translation));
    }

    /**
     * Set service fee amount's to the service comment.
     *
     * The input comment contains a placeholder that is to be replaced by the actual configured value.
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

        $fees = $this->feeProvider->getAmounts($storeId);
        foreach ($shippingOptions as $shippingOption) {
            if (!array_key_exists($shippingOption->getCode(), $fees)) {
                continue;
            }

            foreach ($shippingOption->getInputs() as $input) {
                if (in_array($input->getCode(), ['date', 'enabled', 'details'])) {
                    $this->updateInputComment($input, $fees[$shippingOption->getCode()], $storeId);
                }
            }
        }

        return $shippingOptions;
    }
}
