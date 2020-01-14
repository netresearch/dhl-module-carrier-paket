<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\Stage;

use Dhl\Paket\Model\Pipeline\CreateShipments\ArtifactsContainer;
use Dhl\Paket\Model\Webservice\ShipmentServiceFactory;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Exception\DetailedServiceException;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class SendRequestStage
 *
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
                /** @var ShipmentInterface[] $shipments */
                $shipments = $shipmentService->createShipments($apiRequests);
                // add request id as response index
                foreach ($shipments as $shipment) {
                    $artifactsContainer->addApiResponse($shipment->getSequenceNumber(), $shipment);
                }
            } catch (DetailedServiceException $exception) {
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
            } catch (ServiceException $exception) {
                // mark all requests as failed
                foreach ($requests as $requestIndex => $shipmentRequest) {
                    $artifactsContainer->addError(
                        (string) $requestIndex,
                        $shipmentRequest->getOrderShipment(),
                        'Web service request failed.'
                    );
                }

                // no requests passed the stage
                return [];
            }
        }

        return $requests;
    }
}
