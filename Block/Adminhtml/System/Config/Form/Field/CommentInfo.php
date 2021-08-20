<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Element\Template;
use Netresearch\ConfigFields\Factory\ViewModelFactory;

class CommentInfo extends Field
{
    /**
     * @var ViewModelFactory
     */
    private $viewModelFactory;

    /**
     * @var AbstractElement
     */
    private $element;

    /**
     * CommentInfo constructor.
     *
     * @param Context $context
     * @param ViewModelFactory $viewModelFactory
     */
    public function __construct(Context $context, ViewModelFactory $viewModelFactory)
    {
        $this->viewModelFactory = $viewModelFactory;

        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $this->element = $element;

        return $this->toHtml();
    }

    public function getTemplate(): string
    {
        return 'Dhl_Paket::system/config/commentinfo.phtml';
    }

    /**
     * @return string
     */
    public function renderBody(): string
    {
        $viewModel = $this->element->getData('field_config', 'view_model');
        $template = $this->element->getData('field_config', 'body_template');

        $block = $this->_layout->createBlock(
            Template::class,
            'commentinfo_body_' . $this->element->getHtmlId(),
            [
                'data' => [
                    'template' => $template,
                    'view_model' => $viewModel ? $this->viewModelFactory->create($viewModel) : null
                ],
            ]
        );

        return $block->toHtml();
    }
}
