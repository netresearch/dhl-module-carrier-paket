<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Config\MapBoxConfigInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class MapBoxValidator implements ItemValidatorInterface
{
    use DhlSection;
    use DhlPaketGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var MapBoxConfigInterface
     */
    private $config;

    public function __construct(ResultInterfaceFactory $resultFactory, MapBoxConfigInterface $config)
    {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
    }

    public function execute(int $storeId): ResultInterface
    {
        if ($this->config->getApiToken($storeId)) {
            $status = ResultInterface::OK;
            $message = __('MapBox API token is available.');
        } else {
            $status = ResultInterface::ERROR;
            $message = __('MapBox API token is not configured.');
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Location Finder Map'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
