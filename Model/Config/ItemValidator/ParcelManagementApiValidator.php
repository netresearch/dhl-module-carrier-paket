<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\Paket\Api\ShipmentDateInterface;
use Dhl\Paket\Model\Webservice\ParcelManagementServiceFactory;
use Dhl\Sdk\Paket\ParcelManagement\Exception\ServiceException;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Magento\Framework\Exception\LocalizedException;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class ParcelManagementApiValidator implements ItemValidatorInterface
{
    use DhlSection;
    use DhlPaketGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var ShipmentDateInterface
     */
    private $shipmentDate;

    /**
     * @var ParcelManagementServiceFactory
     */
    private $serviceFactory;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ShipmentDateInterface $shipmentDate,
        ParcelManagementServiceFactory $serviceFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->shipmentDate = $shipmentDate;
        $this->serviceFactory = $serviceFactory;
    }

    public function execute(int $storeId): ResultInterface
    {
        try {
            $startDate = $this->shipmentDate->getDate($storeId);

            $parcelManagementService = $this->serviceFactory->create($storeId);
            $parcelManagementService->getCarrierServices('04229', $startDate);

            $status = ResultInterface::OK;
            $message = __('Parcel Management API connection established successfully.');
        } catch (LocalizedException | ServiceException $exception) {
            $status = ResultInterface::ERROR;
            $message = __('Error: %1', $exception->getMessage());
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Parcel Management Web Service'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
