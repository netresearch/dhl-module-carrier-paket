<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\Stage;

use Dhl\Paket\Model\Pipeline\CreateShipments\ArtifactsContainer;
use Dhl\Paket\Model\Pipeline\CreateShipments\ResponseDataMapper;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class MapResponseStage implements CreateShipmentsStageInterface
{
    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    public function __construct(ResponseDataMapper $responseDataMapper)
    {
        $this->responseDataMapper = $responseDataMapper;
    }

    /**
     * Transform collected results into response objects suitable for processing by the core.
     *
     * The `sequence_number` property is set to the shipment request packages during request mapping.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $stageErrors = $artifactsContainer->getErrors();
        $apiResponses = $artifactsContainer->getApiResponses();

        foreach ($stageErrors as $requestIndex => $details) {
            // no response received from webservice for particular shipment request
            $response = $this->responseDataMapper->createErrorResponse(
                (string) $requestIndex,
                __('Label could not be created: %1', $details['message']),
                $details['shipment']
            );
            $artifactsContainer->addErrorResponse((string) $requestIndex, $response);
        }

        foreach ($requests as $requestIndex => $shipmentRequest) {
            if (isset($stageErrors[$requestIndex])) {
                // errors from previous stages were already processed above
                continue;
            }

            /** @var Shipment $shipment */
            $shipment = $shipmentRequest->getOrderShipment();
            $orderIncrementId = $shipment->getOrder()->getIncrementId();

            foreach ($shipmentRequest->getData('packages') as $packageId => $package) {
                $requestedPackageId = $shipmentRequest->getData('package_id');
                if (!empty($requestedPackageId) && ($requestedPackageId !== $packageId)) {
                    // package was not sent, skip.
                    continue;
                }

                // for DHL Paket requests, this is just a consecutive number
                $sequenceNumber = (string) $package['sequence_number'];
                if (isset($apiResponses[$sequenceNumber])) {
                    // positive response received from webservice
                    $response = $this->responseDataMapper->createShipmentResponse(
                        $apiResponses[$sequenceNumber],
                        $shipmentRequest->getOrderShipment()
                    );

                    $artifactsContainer->addLabelResponse($sequenceNumber, $response);
                } else {
                    // negative response received from webservice, details available in api log
                    $response = $this->responseDataMapper->createErrorResponse(
                        (string) $requestIndex,
                        __('Label for order %1, package %2 could not be created.', $orderIncrementId, $packageId),
                        $shipmentRequest->getOrderShipment()
                    );

                    $artifactsContainer->addErrorResponse((string) $requestIndex, $response);
                }
            }
        }

        return $requests;
    }
}
