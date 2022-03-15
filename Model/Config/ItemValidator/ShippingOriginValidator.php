<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Magento\Framework\Phrase;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class ShippingOriginValidator implements ItemValidatorInterface
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

    private function createResult(string $status, Phrase $message): ResultInterface
    {
        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Shipping Origin'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }

    public function execute(int $storeId): ResultInterface
    {
        if (!$this->config->getOriginStreet($storeId)
            || !$this->config->getOriginCity($storeId)
            || !$this->config->getOriginPostcode($storeId)
            || !$this->config->getOriginCountry($storeId)
        ) {
            $status = ResultInterface::ERROR;
            $message = __('The shipping origin address is incomplete. Please review your <em>Shipping Settings</em> configuration.');
            return $this->createResult($status, $message);
        }

        if ($this->config->getOriginCountry($storeId) !== 'DE') {
            $status = ResultInterface::ERROR;
            $message = __('The shipping origin country must be Germany. Please review your <em>Shipping Settings</em> configuration.');
            return $this->createResult($status, $message);
        }

        $status = ResultInterface::OK;
        $message = __('The shipping origin address is valid for shipping with DHL Parcel Germany.');
        return $this->createResult($status, $message);
    }
}
