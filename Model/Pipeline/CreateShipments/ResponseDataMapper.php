<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments;

use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Phrase;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ReturnShipmentDocumentInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ReturnShipmentDocumentInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentDocumentInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentDocumentInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentResponseInterface;
use Netresearch\ShippingCore\Api\Util\PdfCombinatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Convert API response into the carrier response format that the shipping module understands.
 *
 * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
 */
class ResponseDataMapper
{
    /**
     * @var PdfCombinatorInterface
     */
    private $pdfCombinator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShipmentDocumentInterfaceFactory
     */
    private $shipmentDocumentFactory;

    /**
     * @var ReturnShipmentDocumentInterfaceFactory
     */
    private $returnDocumentFactory;

    /**
     * @var LabelResponseInterfaceFactory
     */
    private $shipmentResponseFactory;

    /**
     * @var ShipmentErrorResponseInterfaceFactory
     */
    private $errorResponseFactory;

    public function __construct(
        PdfCombinatorInterface $pdfCombinator,
        LoggerInterface $logger,
        ShipmentDocumentInterfaceFactory $shipmentDocumentFactory,
        ReturnShipmentDocumentInterfaceFactory $returnDocumentFactory,
        LabelResponseInterfaceFactory $shipmentResponseFactory,
        ShipmentErrorResponseInterfaceFactory $errorResponseFactory
    ) {
        $this->pdfCombinator = $pdfCombinator;
        $this->logger = $logger;
        $this->shipmentDocumentFactory = $shipmentDocumentFactory;
        $this->returnDocumentFactory = $returnDocumentFactory;
        $this->shipmentResponseFactory = $shipmentResponseFactory;
        $this->errorResponseFactory = $errorResponseFactory;
    }

    /**
     * @param ShipmentInterface $shipment
     * @return ShipmentDocumentInterface[]
     */
    private function createDocuments(ShipmentInterface $shipment): array
    {
        $documents = [];

        if ($shipment->getShipmentLabel()) {
            $documents[] = $this->shipmentDocumentFactory->create([
                'data' => [
                    ShipmentDocumentInterface::TITLE => 'PDF Label',
                    ShipmentDocumentInterface::MIME_TYPE => 'application/pdf',
                    ShipmentDocumentInterface::LABEL_DATA => $shipment->getShipmentLabel(),
                    ReturnShipmentDocumentInterface::TRACKING_NUMBER => $shipment->getShipmentNumber(),
                ]
            ]);
        }

        if ($shipment->getReturnLabel()) {
            $documents[] = $this->returnDocumentFactory->create([
                'data' => [
                    ShipmentDocumentInterface::TITLE => __('Return Shipment')->render(),
                    ShipmentDocumentInterface::MIME_TYPE => 'application/pdf',
                    ShipmentDocumentInterface::LABEL_DATA => $shipment->getReturnLabel(),
                    ReturnShipmentDocumentInterface::TRACKING_NUMBER => $shipment->getReturnShipmentNumber(),
                ]
            ]);
        }

        if ($shipment->getExportLabel()) {
            $documents[] = $this->shipmentDocumentFactory->create([
                'data' => [
                    ShipmentDocumentInterface::TITLE => 'Export Label',
                    ShipmentDocumentInterface::MIME_TYPE => 'application/pdf',
                    ShipmentDocumentInterface::LABEL_DATA => $shipment->getExportLabel(),
                ]
            ]);
        }

        if ($shipment->getCodLabel()) {
            $documents[] = $this->shipmentDocumentFactory->create([
                'data' => [
                    ShipmentDocumentInterface::TITLE => 'COD Label',
                    ShipmentDocumentInterface::MIME_TYPE => 'application/pdf',
                    ShipmentDocumentInterface::LABEL_DATA => $shipment->getCodLabel(),
                ]
            ]);
        }

        return $documents;
    }

    /**
     * Map created shipment into response object as required by the shipping module.
     *
     * @param ShipmentInterface $shipment
     * @param \Magento\Sales\Api\Data\ShipmentInterface $salesShipment
     * @return LabelResponseInterface
     */
    public function createShipmentResponse(
        ShipmentInterface $shipment,
        \Magento\Sales\Api\Data\ShipmentInterface $salesShipment
    ): LabelResponseInterface {
        $labelsContent = [];

        // collect all labels from all shipments
        foreach ($shipment->getLabels() as $b64LabelData) {
            if (empty($b64LabelData)) {
                continue;
            }

            $labelsContent[] = $b64LabelData;
        }

        try {
            $shippingLabelContent = $this->pdfCombinator->combineB64PdfPages($labelsContent);
        } catch (RuntimeException $exception) {
            $message = 'Unable to process label data for shipment' . $shipment->getShipmentNumber();
            $this->logger->error($message, ['exception' => $exception]);
            $shippingLabelContent = '';
        }

        $responseData = [
            ShipmentResponseInterface::REQUEST_INDEX => $shipment->getSequenceNumber(),
            ShipmentResponseInterface::SALES_SHIPMENT => $salesShipment,
            LabelResponseInterface::TRACKING_NUMBER => $shipment->getShipmentNumber(),
            LabelResponseInterface::SHIPPING_LABEL_CONTENT => $shippingLabelContent,
            LabelResponseInterface::DOCUMENTS => $this->createDocuments($shipment),
        ];

        return $this->shipmentResponseFactory->create(['data' => $responseData]);
    }

    /**
     * Map error message into response object as required by the shipping module.
     *
     * @param string $requestIndex
     * @param Phrase $message
     * @param \Magento\Sales\Api\Data\ShipmentInterface $salesShipment
     * @return ShipmentErrorResponseInterface
     */
    public function createErrorResponse(
        string $requestIndex,
        Phrase $message,
        \Magento\Sales\Api\Data\ShipmentInterface $salesShipment
    ): ShipmentErrorResponseInterface {
        $responseData = [
            ShipmentResponseInterface::REQUEST_INDEX => $requestIndex,
            ShipmentResponseInterface::SALES_SHIPMENT => $salesShipment,
            ShipmentErrorResponseInterface::ERRORS => $message,
        ];

        return $this->errorResponseFactory->create(['data' => $responseData]);
    }
}
