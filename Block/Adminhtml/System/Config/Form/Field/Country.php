<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Block\Adminhtml\System\Config\Form\Field;

use Magento\Directory\Model\Config\Source\Country as CountrySource;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * The Country dropdown.
 */
class Country extends Select
{
    /**
     * @var CountrySource
     */
    private $source;

    /**
     * Procedures constructor.
     *
     * @param Context $context
     * @param CountrySource $source
     * @param mixed[] $data
     */
    public function __construct(
        Context $context,
        CountrySource $source,
        array $data = []
    ) {
        $this->source = $source;

        parent::__construct($context, $data);
    }

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
            foreach ($this->source->toOptionArray() as $countryData) {
                $this->addOption($countryData['value'], $this->escapeHtml($countryData['label']));
            }
        }

        return parent::_toHtml();
    }
}
