<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Netresearch\ShippingCore\Api\Config\CarrierConfigInterface;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class RatesProviderValidator implements ItemValidatorInterface
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

    /**
     * @var CarrierConfigInterface
     */
    private $carrierConfig;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ModuleConfig $config,
        CarrierConfigInterface $carrierConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
        $this->carrierConfig = $carrierConfig;
    }

    #[\Override]
    public function execute(int $storeId): ResultInterface
    {
        $carrierCode = $this->config->getProxyCarrierCode($storeId);
        if (!$carrierCode) {
            $status = ResultInterface::ERROR;
            $message = __(
                'No offline shipping method is configured to provide rates in checkout. Please review your %1 settings.',
                __('Checkout Presentation')
            );
        } else {
            $carrierTitle = $this->carrierConfig->getTitle($carrierCode, $storeId);
            if (!$this->carrierConfig->isActive($carrierCode, $storeId)) {
                $status = ResultInterface::OK;
                $message = __('%1 provides rates for DHL Parcel Germany.', $carrierTitle);
            } else {
                $status = ResultInterface::NOTICE;
                $message = __('%1 is enabled for checkout. This may lead to duplicated rates in checkout.', $carrierTitle);
            }
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Rates in Checkout'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
