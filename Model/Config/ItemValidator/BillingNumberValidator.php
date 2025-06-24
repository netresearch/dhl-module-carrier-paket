<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Magento\Framework\Phrase;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class BillingNumberValidator implements ItemValidatorInterface
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

    private function createResult(string $status, Phrase $message): ResultInterface
    {
        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Billing Number'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }

    #[\Override]
    public function execute(int $storeId): ResultInterface
    {
        $sandboxMode = $this->config->isSandboxMode($storeId);
        if (!$sandboxMode) {
            $ekp = $this->config->getEkp($storeId);
            if (!$ekp) {
                $status = ResultInterface::ERROR;
                $message = __('EKP (customer number) is not configured. Please review your %1.', __('Account Settings'));
                return $this->createResult($status, $message);
            }

            $participationNumbers = $this->config->getParticipations($storeId);
            if (empty($participationNumbers)) {
                $status = ResultInterface::ERROR;
                $message = __('Participation numbers are not configured. Please review your %1.', __('Account Settings'));
                return $this->createResult($status, $message);
            }
        }

        $status = ResultInterface::OK;
        $message = __('EKP (customer number) and participation numbers are configured.');
        return $this->createResult($status, $message);
    }
}
