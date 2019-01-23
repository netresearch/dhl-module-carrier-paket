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
 * The procedures dropdown is rendered per row using a separate form field.
 * @see Procedures
 *
 * @package Dhl\Paket\Block
 * @author  Benjamin Heuer <benjamin.heuer@netresearch.de>
 * @author  Max Melzer <max.melzer@netresearch.de>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    http://www.netresearch.de/
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

            $this->templateRenderer->setClass('procedure');
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
    protected function _prepareToRender()
    {
        $this->addColumn('procedure', [
            'label'    => __('Procedure'),
            'renderer' => $this->getTemplateRenderer()
        ]);

        $this->addColumn('participation', [
            'label' => __('Participation'),
            'style' => 'width: 80px',
            'class' => 'validate-length maximum-length-2 minimum-length-2 validate-digits'
        ]);

        // Hide "Add after" button
        $this->_addAfter = false;
    }
}
