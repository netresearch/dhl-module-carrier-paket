<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\ShipmentErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Api\ShipmentResponseProcessorInterface;
use Dhl\ShippingCore\Api\TrackResponseProcessorInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class ApiGateway
 *
 * Magento carrier-aware wrapper around the DHL Paket API SDK.
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ApiGateway
{
    /**
     * @var CreateShipmentsPipelineFactory
     */
    private $creationPipelineFactory;

    /**
     * @var DeleteShipmentsPipelineFactory
     */
    private $deletionPipelineFactory;

    /**
     * @var ShipmentServiceInterface
     */
    private $shipmentService;

    /**
     * @var ShipmentResponseProcessorInterface
     */
    private $creationProcessor;

    /**
     * @var TrackResponseProcessorInterface
     */
    private $deletionProcessor;

    /**
     * ApiGateway constructor.
     *
     * @param CreateShipmentsPipelineFactory $creationPipelineFactory
     * @param DeleteShipmentsPipelineFactory $deletionPipelineFactory
     * @param ShipmentServiceInterface $shipmentService
     * @param ShipmentResponseProcessorInterface $creationProcessor
     * @param TrackResponseProcessorInterface $deletionProcessor
     */
    public function __construct(
        CreateShipmentsPipelineFactory $creationPipelineFactory,
        DeleteShipmentsPipelineFactory $deletionPipelineFactory,
        ShipmentServiceInterface $shipmentService,
        ShipmentResponseProcessorInterface $creationProcessor,
        TrackResponseProcessorInterface $deletionProcessor
    ) {
        $this->creationPipelineFactory = $creationPipelineFactory;
        $this->deletionPipelineFactory = $deletionPipelineFactory;
        $this->shipmentService = $shipmentService;
        $this->creationProcessor = $creationProcessor;
        $this->deletionProcessor = $deletionProcessor;
    }

    /**
     * Convert shipment requests to shipment orders, inform label status management, send to API, return result.
     *
     * The mapped result can be
     * - an array of tracking-label pairs or
     * - an array of errors.
     *
     * Note that the SDK does not return errors per shipment, only accumulated into one exception message.
     *
     * @param Request[] $shipmentRequests
     * @return LabelResponseInterface[]|ShipmentErrorResponseInterface[]
     */
    public function createShipments(array $shipmentRequests): array
    {
        $pipeline = $this->creationPipelineFactory->create([
            'shipmentService' => $this->shipmentService,
            'shipmentRequests' => $shipmentRequests
        ]);
        $pipeline->validate()->map()->send()->mapResponse();

        $this->creationProcessor->processResponse($pipeline->getLabels(), $pipeline->getErrors());

        return array_merge($pipeline->getErrors(), $pipeline->getLabels());
    }

    /**
     * Send cancellation request to API, inform label status management, return result.
     *
     * @param TrackRequestInterface[] $cancelRequests
     * @return TrackResponseInterface[]
     */
    public function cancelShipments(array $cancelRequests): array
    {
        /** @var DeleteShipmentsPipeline $pipeline */
        $pipeline = $this->deletionPipelineFactory->create(
            [
                'shipmentService' => $this->shipmentService,
                'cancelRequests' => $cancelRequests
            ]
        );
        $pipeline->map()->send()->mapResponse();

        $this->deletionProcessor->processResponse($pipeline->getTracks(), $pipeline->getErrors());

        return array_merge($pipeline->getErrors(), $pipeline->getTracks());
    }
}
