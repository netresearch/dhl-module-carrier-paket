<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Webservice\Shipment\RequestDataMapper;
use Dhl\Sdk\Paket\Bcs\Api\Data\AuthenticationStorageInterfaceFactory;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Api\ServiceFactoryInterface;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Psr\Log\LoggerInterface;

/**
 * Class ApiGateway
 *
 * Magento carrier-aware wrapper around the DHL Paket API SDK.
 *
 * @package Dhl\Paket\Model
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ApiGateway
{
    /**
     * @var AuthenticationStorageInterfaceFactory
     */
    private $authStorageFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    /**
     * @var LabelGenerator
     */
    private $labelGenerator;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $storeId;

    /**
     * ApiGateway constructor.
     * @param DataObjectFactory $dataObjectFactory
     * @param ModuleConfig $moduleConfig
     * @param AuthenticationStorageInterfaceFactory $authStorageFactory
     * @param RequestDataMapper $requestDataMapper
     * @param ServiceFactoryInterface $serviceFactory
     * @param LabelGenerator $labelGenerator
     * @param LoggerInterface $logger
     * @param int $storeId
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        ModuleConfig $moduleConfig,
        AuthenticationStorageInterfaceFactory $authStorageFactory,
        RequestDataMapper $requestDataMapper,
        ServiceFactoryInterface $serviceFactory,
        LabelGenerator $labelGenerator,
        LoggerInterface $logger,
        int $storeId = 0
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->moduleConfig = $moduleConfig;
        $this->authStorageFactory = $authStorageFactory;
        $this->requestDataMapper = $requestDataMapper;
        $this->serviceFactory = $serviceFactory;
        $this->labelGenerator = $labelGenerator;
        $this->logger = $logger;
        $this->storeId = $storeId;
    }

    /**
     * Create the SDK service.
     *
     * @return ShipmentServiceInterface
     */
    private function getShipmentService(): ShipmentServiceInterface
    {
        $authStorage = $this->authStorageFactory->create([
            'applicationId' => $this->moduleConfig->getAuthUsername($this->storeId),
            'applicationToken' => $this->moduleConfig->getAuthPassword($this->storeId),
            'user' => $this->moduleConfig->getUser($this->storeId),
            'signature' => $this->moduleConfig->getSignature($this->storeId)
        ]);

        $service = $this->serviceFactory->createShipmentService(
            $authStorage,
            $this->logger,
            $this->moduleConfig->isSandboxMode($this->storeId)
        );

        return $service;
    }

    /**
     * Map errors into error object.
     *
     * @param string[] $messages
     * @return DataObject[]
     */
    private function createErrorResponse(array $messages): array
    {
        $message = implode(' ', $messages);
        $response = $this->dataObjectFactory->create(['data' => ['errors' => $message]]);
        return [$response];
    }

    /**
     * Map created shipments into data objects.
     *
     * @param ShipmentInterface[] $shipments
     * @return DataObject[]
     */
    private function createShipmentResponse(array $shipments): array
    {
        $response = array_map(function (ShipmentInterface $shipment) {
            // todo(nr): move label combination to shipping core?
            // convert b64 into binary strings
            foreach ($shipment->getLabels() as $b64LabelData) {
                if (empty($b64LabelData)) {
                    continue;
                }

                $labelsContent[]= base64_decode($b64LabelData);
            }

            // merge labels if necessary
            if (empty($labelsContent)) {
                // no label returned
                $shippingLabelContent = '';
            } elseif (count($labelsContent) < 2) {
                // exactly one label returned, use it as-is
                $shippingLabelContent = $labelsContent[0];
            } else {
                // multiple labels returned, merge into one pdf file
                $shippingLabelContent = $this->labelGenerator->combineLabelsPdf($labelsContent)->render();
            }

            $responseData = [
                'tracking_number' => $shipment->getShipmentNumber(),
                'shipping_label_content' => $shippingLabelContent,
            ];

            return $this->dataObjectFactory->create(['data' => $responseData]);
        }, $shipments);

        return $response;
    }

    /**
     * Convert shipment requests to shipment orders, send to API, return result.
     *
     * The mapped result can be
     * - an array of tracking-label pairs or
     * - an array of errors.
     *
     * Note that the SDK does not return errors per shipment, only accumulated into one exception message.
     *
     * @param \Magento\Shipping\Model\Shipment\Request[] $shipmentRequests
     * @return DataObject[]
     */
    public function createShipments(array $shipmentRequests): array
    {
        $returnRequests = array_filter($shipmentRequests, function (DataObject $request) {
            return ($request->getData('is_return') || $request instanceof ReturnShipment);
        });

        if (!empty($returnRequests)) {
            $message = __('Return shipments are not supported.');
            $response = $this->createErrorResponse([$message]);
            return $response;
        }

        $service = $this->getShipmentService();
        $shipmentOrders = array_map(function (Request $shipmentRequest) {
            return $this->requestDataMapper->mapRequest($shipmentRequest);
        }, $shipmentRequests);

        try {
            $shipments = $service->createShipments($shipmentOrders);
            $response = $this->createShipmentResponse($shipments);

            return $response;
        } catch (ServiceException $exception) {
            $message = __('Requested shipments could not be created: %s', $exception->getMessage());
            $response = $this->createErrorResponse([$message]);

            return $response;
        }
    }

    /**
     * Send cancellation request to API, return result.
     *
     * @param string[] $shipmentNumbers
     * @return string[]
     */
    public function cancelShipments(array $shipmentNumbers): array
    {
        $service = $this->getShipmentService();
        try {
            $cancelled = $service->cancelShipments($shipmentNumbers);
            return $cancelled;
        } catch (ServiceException $exception) {
            return [];
        }
    }
}
