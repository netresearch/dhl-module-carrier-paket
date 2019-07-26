<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Pipeline\CreateShipments\Stage;

use Dhl\Paket\Webservice\Pipeline\CreateShipments\ArtifactsContainer;
use Dhl\Paket\Webservice\ShipmentServiceFactory;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class SendRequestStage
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class SendRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var ShipmentServiceFactory
     */
    private $shipmentServiceFactory;

    /**
     * SendRequestStage constructor.
     * @param ShipmentServiceFactory $shipmentServiceFactory
     */
    public function __construct(ShipmentServiceFactory $shipmentServiceFactory)
    {
        $this->shipmentServiceFactory = $shipmentServiceFactory;
    }

    /**
     * Send label request objects to shipment service.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $apiRequests = $artifactsContainer->getApiRequests();
        if (!empty($apiRequests)) {
            $shipmentService = $this->shipmentServiceFactory->create(['storeId' => $artifactsContainer->getStoreId()]);

            try {
                $shipments = $shipmentService->createShipments($apiRequests);
                // add request id as response index
                foreach ($shipments as $shipment) {
                    $artifactsContainer->addApiResponse($shipment->getSequenceNumber(), $shipment);
                }
            } catch (ServiceException $exception) {
                // mark all requests as failed
                foreach ($requests as $requestIndex => $shipmentRequest) {
                    $artifactsContainer->addError(
                        (string) $requestIndex,
                        $shipmentRequest->getOrderShipment(),
                        $exception->getMessage()
                    );
                }

                // no requests passed the stage
                return [];
            }
        }

        return $requests;
    }
}
