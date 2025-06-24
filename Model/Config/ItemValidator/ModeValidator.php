<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class ModeValidator implements ItemValidatorInterface
{
    use DhlSection;
    use DhlPaketGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var ModuleConfig
     */
    private $config;

    public function __construct(ResultInterfaceFactory $resultFactory, ModuleConfig $config)
    {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
    }

    #[\Override]
    public function execute(int $storeId): ResultInterface
    {
        if ($this->config->isSandboxMode($storeId)) {
            $status = ResultInterface::INFO;
            $message = __('The application runs in sandbox mode. Labels cannot be used for shipping.');
        } else {
            $status = ResultInterface::OK;
            $message = __('The application runs in production mode. Labels can be used for shipping.');
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Application Mode'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
