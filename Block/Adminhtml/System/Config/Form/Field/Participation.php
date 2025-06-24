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

/**
 * Array configuration field with procedures and the merchant's participation number.
 *
 * The procedures dropdown is rendered per row using a separate form field.
 * @see \Dhl\Paket\Block\Adminhtml\System\Config\Form\Field\Procedures
 */
class Participation extends AbstractFieldArray
{
    /**
     * @var Procedures
     */
    private $templateRenderer;

    /**
     * Create renderer used for displaying the participation select element.
     *
     * @return Procedures|BlockInterface
     *
     * @throws LocalizedException
     */
    private function getTemplateRenderer()
    {
        if (!$this->templateRenderer) {
            $this->templateRenderer = $this->getLayout()->createBlock(
                Procedures::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );

            $this->templateRenderer->setClass('select admin__control-select');
        }

        return $this->templateRenderer;
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
        $hash = $this->getTemplateRenderer()->calcOptionHash(
            $row->getData('procedure')
        );

        $row->setData(
            'option_extra_attrs',
            [
                'option_' . $hash => 'selected="selected"',
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
        $this->addColumn('procedure', [
            'label'    => __('Procedure'),
            'renderer' => $this->getTemplateRenderer(),
        ]);

        $this->addColumn('participation', [
            'label' => __('Participation'),
            'style' => 'width: 80px',
            'class' => 'validate-no-empty validate-length maximum-length-2 minimum-length-2 validate-alphanum input-text admin__control-text',
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
