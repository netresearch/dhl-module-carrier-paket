<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments;

use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterfaceFactory;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterfaceFactory;
use Dhl\ShippingCore\Api\Util\PdfCombinatorInterface;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

/**
 * Response mapper.
 *
 * Convert API response into the carrier response format that the shipping module understands.
 *
 * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
 *
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
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
     * @var LabelResponseInterfaceFactory
     */
    private $shipmentResponseFactory;

    /**
     * @var ShipmentErrorResponseInterfaceFactory
     */
    private $errorResponseFactory;

    /**
     * ResponseDataMapper constructor.
     *
     * @param PdfCombinatorInterface $pdfCombinator
     * @param LabelResponseInterfaceFactory $shipmentResponseFactory
     * @param ShipmentErrorResponseInterfaceFactory $errorResponseFactory
     */
    public function __construct(
        PdfCombinatorInterface $pdfCombinator,
        LabelResponseInterfaceFactory $shipmentResponseFactory,
        ShipmentErrorResponseInterfaceFactory $errorResponseFactory
    ) {
        $this->pdfCombinator = $pdfCombinator;
        $this->shipmentResponseFactory = $shipmentResponseFactory;
        $this->errorResponseFactory = $errorResponseFactory;
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
            LabelResponseInterface::REQUEST_INDEX => $shipment->getSequenceNumber(),
            LabelResponseInterface::SALES_SHIPMENT => $salesShipment,
            LabelResponseInterface::TRACKING_NUMBER => $shipment->getShipmentNumber(),
            LabelResponseInterface::SHIPPING_LABEL_CONTENT => $shippingLabelContent,
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
            ShipmentErrorResponseInterface::REQUEST_INDEX => $requestIndex,
            ShipmentErrorResponseInterface::ERRORS => $message,
            ShipmentErrorResponseInterface::SALES_SHIPMENT => $salesShipment,
        ];

        return $this->errorResponseFactory->create(['data' => $responseData]);
    }
}
