<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Block\Adminhtml\System\Config\Form\Field;

use Dhl\Paket\Util\ShippingProducts;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Config field block for the Default Product select field.
 *
 * @package Dhl\Paket\Block
 * @author  Max Melzer <max.melzer@netresearch.de>
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class DefaultProduct extends Field
{
    /**
     * Retrieve HTML markup for one region (one set of radio buttons with label).
     *
     * @param string $destinationId
     * @param string $elmId
     * @param string $elmName
     * @param string $elmValue
     * @param bool $isDisabled
     * @param string[] $applicableProducts
     * @return string
     */
    private function getRegionHtml(
        string $destinationId,
        string $elmId,
        string $elmName,
        string $elmValue,
        bool $isDisabled,
        array $applicableProducts
    ) {
        // prepare one radio button per product
        $inputsHtml = '';

        foreach ($applicableProducts as $applicableProduct) {
            $productCode = $applicableProduct['value'];
            $productName = $applicableProduct['label'];

            $checked = ($productCode === $elmValue) ? 'checked="checked"' : '';
            $disabled = $isDisabled ? 'disabled="disabled"' : '';
            $class = 'admin__control-radio';
            $class.= $isDisabled ? ' disabled' : '';

            $inputsHtml.= <<<HTML
<div class="input-wrap">
    <input type="radio" class="{$class}" {$checked} {$disabled}
           value="{$productCode}" name="{$elmName}[{$destinationId}]" id="{$elmId}{$destinationId}_{$productCode}" />
    <label class="admin__field-label" for="{$elmId}{$destinationId}_{$productCode}"><span>$productName</span></label>
</div>

HTML;
        }

        // add all radio buttons to the region radio set
        $regionLabel = __('Ship to %1', $destinationId);
        $regionHtml = <<<HTML
<div class="admin__field field field-default_product_$destinationId">
    <label for="default_product_$destinationId" class="label admin__field-label">$regionLabel</label>
    <div class="admin__field-control control">
        $inputsHtml
    </div>
</div>
HTML;

        return $regionHtml;
    }

    /**
     * Retrieve HTML markup for each destination coming from the config source.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $fieldsHtml = '';

        $values = $element->getData('values');
        if (empty($values)) {
            $values = [
                ShippingProducts::REGION_INTERNATIONAL => [
                    [
                        'value' => '__empty',
                        'label' => __('DHL Paket does not offer services for your shipping origin.')
                    ],
                ]
            ];
        }

        foreach ($values as $destinationId => $applicableProducts) {
            $fieldsHtml.= $this->getRegionHtml(
                $destinationId,
                $element->getHtmlId(),
                $element->getName(),
                $element->getData('value')[$destinationId] ?? '',
                (bool) $element->getData('disabled'),
                $applicableProducts
            );
        }

        return '<div class="dhlpaket_default_product">' . $fieldsHtml . '</div>';
    }
}
