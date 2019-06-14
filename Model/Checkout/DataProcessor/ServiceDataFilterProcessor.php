<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor;

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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ServiceDataFilterProcessor constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
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

        foreach ($optionsData as $optionData) {
            $code = $optionData->getCode();
            $isEnabled = (bool)$this->scopeConfig->getValue(
                'dhlshippingsolutions/dhlpaket/dhl_paket_additional_services/service_'.strtolower($code).'_enabled',
                ScopeInterface::SCOPE_STORE,
                $scopeId
            );

            if (!$isEnabled) {
                unset($optionsData[$optionData->getCode()]);
            }
        }

        return $optionsData;
    }
}
