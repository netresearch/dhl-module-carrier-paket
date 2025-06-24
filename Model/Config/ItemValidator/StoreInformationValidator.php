<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class StoreInformationValidator implements ItemValidatorInterface
{
    use DhlSection;
    use DhlPaketGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var ShippingConfigInterface
     */
    private $config;

    public function __construct(ResultInterfaceFactory $resultFactory, ShippingConfigInterface $config)
    {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
    }

    #[\Override]
    public function execute(int $storeId): ResultInterface
    {
        $storeInformation = $this->config->getStoreInformation($storeId);

        if (!$storeInformation->getData('name')
            || !$storeInformation->getData('phone')
        ) {
            $status = ResultInterface::ERROR;
            $message = __('The store information is incomplete. Please add name and phone number in the <em>General</em> settings.');
        } else {
            $status = ResultInterface::OK;
            $message = __('The store information is complete.');
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Store Information'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
