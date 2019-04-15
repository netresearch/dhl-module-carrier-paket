<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Paket\Webservice\CarrierResponse\ErrorResponse;
use Dhl\Paket\Webservice\CarrierResponse\ErrorResponseFactory;
use Dhl\Paket\Webservice\CarrierResponse\FailureResponse;
use Dhl\Paket\Webservice\CarrierResponse\FailureResponseFactory;
use Dhl\Paket\Webservice\CarrierResponse\ShipmentResponse;
use Dhl\Paket\Webservice\CarrierResponse\ShipmentResponseFactory;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\ShippingCore\Api\PdfCombinatorInterface;
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
 * @package Dhl\Paket\Webservice
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
     * @var ShipmentResponseFactory
     */
    private $shipmentResponseFactory;

    /**
     * @var ErrorResponseFactory
     */
    private $errorResponseFactory;

    /**
     * @var FailureResponseFactory
     */
    private $failureResponseFactory;

    /**
     * ResponseDataMapper constructor.
     *
     * @param PdfCombinatorInterface $pdfCombinator
     * @param ShipmentResponseFactory $shipmentResponseFactory
     * @param ErrorResponseFactory $errorResponseFactory
     * @param FailureResponseFactory $failureResponseFactory
     */
    public function __construct(
        PdfCombinatorInterface $pdfCombinator,
        ShipmentResponseFactory $shipmentResponseFactory,
        ErrorResponseFactory $errorResponseFactory,
        FailureResponseFactory $failureResponseFactory
    ) {
        $this->pdfCombinator = $pdfCombinator;
        $this->shipmentResponseFactory = $shipmentResponseFactory;
        $this->errorResponseFactory = $errorResponseFactory;
        $this->failureResponseFactory = $failureResponseFactory;
    }

    /**
     * Map created shipment into response object as required by the shipping module.
     *
     * @param string $sequenceNumber
     * @param ShipmentInterface $shipment
     * @return ShipmentResponse
     */
    public function createShipmentResponse(string $sequenceNumber, ShipmentInterface $shipment): ShipmentResponse
    {
        $labelsContent = [];

        // collect all labels from all shipments
        foreach ($shipment->getLabels() as $b64LabelData) {
            if (empty($b64LabelData)) {
                continue;
            }

            $labelsContent[]= $b64LabelData;
        }

        try {
            $shippingLabelContent = $this->pdfCombinator->combineB64PdfPages($labelsContent);
        } catch (RuntimeException $exception) {
            $message = 'Unable to process label data for shipment' . $shipment->getShipmentNumber();
            $this->logger->error($message, ['exception' => $exception]);
            $shippingLabelContent = '';
        }

        $responseData = [
            'sequence_number' => $sequenceNumber,
            'tracking_number' => $shipment->getShipmentNumber(),
            'shipping_label_content' => $shippingLabelContent,
        ];

        return $this->shipmentResponseFactory->create(['data' => $responseData]);
    }

    /**
     * Map error message into response object as required by the shipping module.
     *
     * @param string $sequenceNumber
     * @param Phrase $message
     * @return ErrorResponse
     */
    public function createErrorResponse(string $sequenceNumber, Phrase $message): ErrorResponse
    {
        $responseData = [
            'sequence_number' => $sequenceNumber,
            'errors' => $message
        ];
        return $this->errorResponseFactory->create(['data' => $responseData]);
    }

    /**
     * Map web service error into response object as required by the shipping module.
     *
     * @param Phrase $message
     * @return FailureResponse
     */
    public function createFailureResponse(Phrase $message)
    {
        $responseData = [
            'errors' => $message
        ];
        return $this->failureResponseFactory->create(['data' => $responseData]);
    }
}
