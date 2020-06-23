<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class Routes extends Select
{
    /**
     * @param string $value
     *
     * @return self
     */
    public function setInputName(string $value): self
    {
        return $this->setData('name', $value);
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setInputId(string $value): self
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->addOption('0', __('Select Route'));
            $this->addOption('DE-DE', __('Domestic'));
            $this->addOption('DE-EU', __('EU'));
            $this->addOption('DE-INTL', __('International (excl. EU)'));
        }

        return parent::_toHtml();
    }
}
