<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Adminhtml\System\Config\Source;

use Dhl\Paket\Util\ShippingProducts;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Procedure
 *
 * @package Dhl\Paket\Model
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Procedure implements OptionSourceInterface
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
     * Procedure constructor.
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
     * From a list of procedures, return only those applicable to the current shipping origin.
     *
     * @return string[]
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

        $procedures = [
            ShippingProducts::PROCEDURE_NATIONAL            => __('DHL Paket: V01PAK'),
            ShippingProducts::PROCEDURE_NATIONAL_TAGGLEICH  => __('DHL Paket Taggleich: V06PAK'),
            ShippingProducts::PROCEDURE_INTERNATIONAL       => __('DHL Paket International: V53WPAK'),
            ShippingProducts::PROCEDURE_EUROPAKET           => __('DHL Europaket: V54EPAK'),

            ShippingProducts::PROCEDURE_RETURNSHIPMENT_NATIONAL => __('Retoure DHL Paket: V07PAK'),
        ];

        $applicableProcedures = $this->shippingProducts->getApplicableProcedures($originCountry);

        return array_intersect_key($procedures, array_flip($applicableProcedures));
    }

    /**
     * Options getter.
     *
     * @return mixed[]
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->getOptions() as $procedure => $label) {
            $options[] = [
                // Casting is required as the value otherwise may be interpreted as integer!
                'value' => (string) $procedure,
                'label' => $label,
            ];
        }

        return $options;
    }
}
