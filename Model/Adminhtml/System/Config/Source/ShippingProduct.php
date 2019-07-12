<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Dhl\Paket\Util\ShippingProducts;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class ShippingProduct
 *
 * @package Dhl\Paket\Model
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ShippingProduct implements ArrayInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

    /**
     * ShippingProduct constructor.
     *
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param ShippingProducts $shippingProducts
     */
    public function __construct(
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        ShippingProducts $shippingProducts
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->shippingProducts = $shippingProducts;
    }

    /**
     * @return string[][]
     */
    public function getOptions(): array
    {
        // try to load website id from request params, fall back to default
        $websiteId = $this->request->getParam('website', 0);

        $originCountry = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_COUNTRY_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        // load product codes applicable to the given origin
        $productCodes = $this->shippingProducts->getApplicableProducts($originCountry);

        // add human-readable product names
        $options = [];
        foreach ($productCodes as $destinationRegion => $shippingProducts) {
            foreach ($shippingProducts as $productCode) {
                $options[$destinationRegion][$productCode] = $this->shippingProducts->getProductName($productCode);
            }
        }

        return $options;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return string[][] Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getOptions() as $region => $products) {
            foreach ($products as $productCode => $productName) {
                $options[$region][] = [
                    'value' => $productCode,
                    'label' => $productName,
                ];
            }
        }

        return $options;
    }
}
