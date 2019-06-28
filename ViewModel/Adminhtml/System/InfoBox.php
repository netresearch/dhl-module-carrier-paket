<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\ViewModel\Adminhtml\System;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Model\Config\Config;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class InfoBox
 *
 * @package   Dhl\Paket\ViewModel
 * @author    Max Melzer <max.melzer@netresearch.de>
 * @link      http://www.netresearch.de/
 */
class InfoBox implements ArgumentInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * InfoBox constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getModuleVersion(): string
    {
        return $this->config->getModuleVersion();
    }

    public function getModuleTitle(): string
    {
        return 'DHL Paket Business Customer Shipping';
    }
}
