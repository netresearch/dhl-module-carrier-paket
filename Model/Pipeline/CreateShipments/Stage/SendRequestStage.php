<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\Stage;

use Dhl\Paket\Model\Pipeline\CreateShipments\ArtifactsContainer;
use Dhl\Paket\Model\Webservice\ShipmentServiceFactory;
use Dhl\Sdk\ParcelDe\Shipping\Api\Data\OrderConfigurationInterfaceFactory;
use Dhl\Sdk\ParcelDe\Shipping\Exception\DetailedServiceException;
use Dhl\Sdk\ParcelDe\Shipping\Exception\ServiceException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class SendRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var ShipmentServiceFactory
     */
    private $shipmentServiceFactory;

    /**
     * @var OrderConfigurationInterfaceFactory
     */
    private $orderConfigFactory;

    public function __construct(
        ShipmentServiceFactory $shipmentServiceFactory,
        OrderConfigurationInterfaceFactory $orderConfigFactory
    ) {
        $this->shipmentServiceFactory = $shipmentServiceFactory;
        $this->orderConfigFactory = $orderConfigFactory;
    }

    /**
     * Send label request objects to shipment service.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    #[\Override]
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $apiRequests = $artifactsContainer->getApiRequests();
        if (!empty($apiRequests)) {
            $shipmentService = $this->shipmentServiceFactory->create(['storeId' => $artifactsContainer->getStoreId()]);
            $orderConfig = $this->orderConfigFactory->create(['storeId' => $artifactsContainer->getStoreId()]);

            try {
                $shipments = $shipmentService->createShipments($apiRequests, $orderConfig);
                // add request id as response index
                foreach ($shipments as $shipment) {
                    $artifactsContainer->addApiResponse($shipment->getRequestIndex(), $shipment);
                }
            } catch (DetailedServiceException $exception) {
                // mark all requests as failed
                foreach ($requests as $requestIndex => $shipmentRequest) {
                    $artifactsContainer->addError(
                        $requestIndex,
                        $shipmentRequest->getOrderShipment(),
                        $exception->getMessage()
                    );
                }

                // no requests passed the stage
                return [];
            } catch (ServiceException) {
                // mark all requests as failed
                foreach ($requests as $requestIndex => $shipmentRequest) {
                    $msg = 'Web service request failed.';
                    $artifactsContainer->addError($requestIndex, $shipmentRequest->getOrderShipment(), $msg);
                }
                // no requests passed the stage
                return [];
            }
        }
        return $requests;
    }
}
