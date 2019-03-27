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
use Magento\Framework\Phrase;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Psr\Log\LoggerInterface;

/**
 * Response mapper.
 *
 * Convert API response into the carrier response format that the shipping module understands.
 *
 * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
 *
 * @package Dhl\Paket\Model
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ResponseDataMapper
{
    /**
     * @var LabelGenerator
     */
    private $labelGenerator;

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
     * @param LabelGenerator $labelGenerator
     * @param ShipmentResponseFactory $shipmentResponseFactory
     * @param ErrorResponseFactory $errorResponseFactory
     * @param FailureResponseFactory $failureResponseFactory
     */
    public function __construct(
        LabelGenerator $labelGenerator,
        ShipmentResponseFactory $shipmentResponseFactory,
        ErrorResponseFactory $errorResponseFactory,
        FailureResponseFactory $failureResponseFactory
    ) {
        $this->labelGenerator = $labelGenerator;
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
            try {
                $shippingLabelContent = $this->labelGenerator->combineLabelsPdf($labelsContent)->render();
            } catch (\Zend_Pdf_Exception $exception) {
                $message = 'Unable to process label data for shipment' . $shipment->getShipmentNumber();
                $this->logger->error($message, ['exception' => $exception]);
                $shippingLabelContent = '';
            }
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
