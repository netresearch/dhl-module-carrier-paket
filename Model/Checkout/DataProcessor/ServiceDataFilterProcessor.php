<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\AbstractProcessor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ServiceDataFilterProcessor
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author   Andreas Müller <andreas.mueller@netresearch.de>
 */
class ServiceDataFilterProcessor extends AbstractProcessor
{
    /**
     * @var ModuleConfig
     */
    private $modulConfig;

    /**
     * ServiceDataFilterProcessor constructor.
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        ModuleConfig $moduleConfig
    ) {
        $this->modulConfig = $moduleConfig;
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param string $countryId
     * @param string $postalCode
     * @param int|null $scopeId
     * @return ShippingOptionInterface[]
     */
    public function processShippingOptions(
        array $optionsData,
        string $countryId,
        string $postalCode,
        int $scopeId = null
    ): array {

        $configValues = $this->modulConfig->getCheckoutServices($scopeId);

        foreach ($optionsData as $optionData) {
            $code = strtolower($optionData->getCode());
            if (isset($configValues[$code]) &&
                !(bool)$configValues[$code]) {
                unset($optionsData[$optionData->getCode()]);
            }
        }

        return $optionsData;
    }
}
