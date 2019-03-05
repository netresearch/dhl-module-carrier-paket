<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Block\Adminhtml\System\Config\Form\Field;

use Dhl\Paket\Model\Adminhtml\System\Config\Source\Procedure;
use Dhl\Paket\Model\ShippingProducts\ShippingProductsInterface;
use Dhl\ShippingCore\Model\Config\CoreConfigInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Store\Model\ScopeInterface;

/**
 * The procedures dropdown.
 *
 * @package Dhl\Paket\Block
 * @author  Benjamin Heuer <benjamin.heuer@netresearch.de>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    http://www.netresearch.de/
 */
class Procedures extends Select
{
    /**
     * @var Procedure
     */
    private $source;

    /**
     * @var CoreConfigInterface
     */
    private $shippingCoreConfig;

    /**
     * @var ShippingProductsInterface
     */
    private $shippingProducts;

    /**
     * Procedures constructor.
     *
     * @param Context                   $context
     * @param CoreConfigInterface       $shippingCoreConfig
     * @param Procedure                 $source
     * @param ShippingProductsInterface $shippingProducts
     * @param array                     $data
     */
    public function __construct(
        Context $context,
        CoreConfigInterface $shippingCoreConfig,
        Procedure $source,
        ShippingProductsInterface $shippingProducts,
        array $data = []
    ) {
        $this->source             = $source;
        $this->shippingCoreConfig = $shippingCoreConfig;
        $this->shippingProducts   = $shippingProducts;

        parent::__construct($context, $data);
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setInputName($value): self
    {
        return $this->setData('name', $value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->addOption('0', __('Select Procedure'));

            $procedures = $this->filterAvailable($this->source->toOptionArray());

            foreach ($procedures as $procedureData) {
                $this->addOption($procedureData['value'], $this->escapeHtml($procedureData['label']));
            }
        }

        return parent::_toHtml();
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function filterAvailable(array $data): array
    {
        $originCountry       = $this->shippingCoreConfig->getOriginCountry(null, ScopeInterface::SCOPE_WEBSITE);
        $availableProcedures = $this->shippingProducts->getApplicableProcedures($originCountry);

        $data = array_filter($data, function ($element) use ($availableProcedures) {
            return \in_array($element['value'], $availableProcedures, true);
        });

        return $data;
    }
}
