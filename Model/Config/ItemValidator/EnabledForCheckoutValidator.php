<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Netresearch\ShippingCore\Api\Config\CarrierConfigInterface;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class EnabledForCheckoutValidator implements ItemValidatorInterface
{
    use DhlSection;
    use DhlPaketGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var CarrierConfigInterface
     */
    private $config;

    public function __construct(ResultInterfaceFactory $resultFactory, CarrierConfigInterface $config)
    {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
    }

    #[\Override]
    public function execute(int $storeId): ResultInterface
    {
        if ($this->config->isActive(Paket::CARRIER_CODE, $storeId)) {
            $status = ResultInterface::OK;
            $message = __('DHL Parcel Germany is enabled for checkout.');
        } else {
            $status = ResultInterface::ERROR;
            $message = __('DHL Parcel Germany is not enabled for checkout.');
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Checkout'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
