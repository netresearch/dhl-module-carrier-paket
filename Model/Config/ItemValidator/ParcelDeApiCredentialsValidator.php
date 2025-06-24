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

class ParcelDeApiCredentialsValidator implements ItemValidatorInterface
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

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ModuleConfig $config
    ) {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
    }

    #[\Override]
    public function execute(int $storeId): ResultInterface
    {
        $sandboxMode = $this->config->isSandboxMode($storeId);
        if ($sandboxMode || ($this->config->getUser($storeId) && $this->config->getSignature($storeId))) {
            $status = ResultInterface::OK;
            $message = __('Web service credentials are configured.');
        } else {
            $status = ResultInterface::ERROR;
            $message = __(
                'Web service credentials are incomplete. Please review your %1.',
                __('Account Settings')
            );
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Business Customer Shipping'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
