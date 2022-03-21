<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\Rate\ResponseProcessor;

use Dhl\Paket\Model\Config\ModuleConfig;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Netresearch\ShippingCore\Api\Pipeline\RateResponseProcessorInterface;

class MethodTitle implements RateResponseProcessorInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    public function __construct(ModuleConfig $moduleConfig)
    {
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Override shipping method title.
     *
     * @param Method[] $methods List of rate methods
     * @param RateRequest|null $request The rate request
     *
     * @return Method[]
     */
    public function processMethods(array $methods, RateRequest $request = null): array
    {
        $storeId = ($request instanceof RateRequest) ? $request->getData('store_id') : null;
        $methodTitle = $this->moduleConfig->getMethodName($storeId);
        if (!$methodTitle) {
            return $methods;
        }

        foreach ($methods as $method) {
            $method->setData('method_title', $methodTitle);
        }

        return $methods;
    }
}
