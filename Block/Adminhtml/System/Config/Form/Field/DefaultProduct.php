<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

class DefaultProduct extends AbstractFieldArray
{
    /**
     * @var Routes
     */
    private $routeRenderer;

    /**
     * @var Products
     */
    private $productRenderer;

    /**
     * Create renderer for displaying the "route" select element.
     *
     * @return Routes|BlockInterface
     *
     * @throws LocalizedException
     */
    private function getRouteRenderer()
    {
        if (!$this->routeRenderer) {
            $this->routeRenderer = $this->getLayout()->createBlock(
                Routes::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );

            $this->routeRenderer->setClass('select admin__control-select');
        }

        return $this->routeRenderer;
    }

    /**
     * Create renderer for displaying the "product" select element.
     *
     * @return Routes|BlockInterface
     *
     * @throws LocalizedException
     */
    private function getProductRenderer()
    {
        if (!$this->productRenderer) {
            $this->productRenderer = $this->getLayout()->createBlock(
                Products::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );

            $this->productRenderer->setClass('select admin__control-select');
        }

        return $this->productRenderer;
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row
     *
     * @throws LocalizedException
     */
    #[\Override]
    protected function _prepareArrayRow(DataObject $row)
    {
        $routeHash = $this->getRouteRenderer()->calcOptionHash($row->getData('route'));
        $productHash = $this->getProductRenderer()->calcOptionHash($row->getData('product'));

        $row->setData(
            'option_extra_attrs',
            [
                'option_' . $routeHash => 'selected="selected"',
                'option_' . $productHash => 'selected="selected"',
            ]
        );
    }

    /**
     * Prepare to render.
     *
     * @throws LocalizedException
     */
    #[\Override]
    protected function _prepareToRender()
    {
        $this->addColumn('route', [
            'label'    => __('Route'),
            'renderer' => $this->getRouteRenderer(),
        ]);

        $this->addColumn('product', [
            'label' => __('Product'),
            'renderer' => $this->getProductRenderer(),
        ]);

        // Hide "Add after" button
        $this->_addAfter = false;
    }

    /**
     * Append invisible inherit elements.
     *
     * On non-default scope, the combined field's individual inputs get enabled by the
     * FormElementDependenceController although "Use Default" is checked for the overall field.
     * The workaround is to add a hidden fake "Use Default" input to each of the fields contained in the field array.
     *
     * @see FormElementDependenceController.trackChange
     * @link https://github.com/magento/magento2/blob/2.2.0/lib/web/mage/adminhtml/form.js#L474
     *
     * @param string $columnName
     * @return string
     * @throws \Exception
     */
    #[\Override]
    public function renderCellTemplate($columnName)
    {
        $cellTemplate = parent::renderCellTemplate($columnName);

        if ($this->getData('element') && $this->getData('element')->getData('inherit')) {
            $htmlId = $this->_getCellInputElementId('<%- _id %>', $columnName);
            $inherit = '<input type="hidden" id="' . $htmlId . '_inherit" checked="checked" disabled="disabled" />';
            $cellTemplate.= $inherit;
        }

        return $cellTemplate;
    }
}
