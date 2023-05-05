<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\Bcs\Api\Data\OrderConfigurationInterface;

class ShipmentOrderConfig implements OrderConfigurationInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var int
     */
    private $storeId;

    public function __construct(ModuleConfig $moduleConfig, int $storeId)
    {
        $this->moduleConfig = $moduleConfig;
        $this->storeId = $storeId;
    }

    public function mustEncode(): ?bool
    {
        return $this->moduleConfig->isPrintOnlyIfCodeable($this->storeId);
    }

    public function isCombinedPrinting(): ?bool
    {
        return true;
    }

    public function getDocFormat(): ?string
    {
        return OrderConfigurationInterface::DOC_FORMAT_PDF;
    }

    public function getPrintFormat(): ?string
    {
        return null;
    }

    public function getPrintFormatReturn(): ?string
    {
        return null;
    }

    public function getProfile(): string
    {
        return $this->moduleConfig->getGroupProfile($this->storeId);
    }
}
