<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Shipping\Model\Shipping\LabelGenerator;

/**
 * Response mapper.
 *
 * Convert API response into the carrier response format that the shipping module understands.
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
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * ResponseDataMapper constructor.
     * @param LabelGenerator $labelGenerator
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        LabelGenerator $labelGenerator,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->labelGenerator = $labelGenerator;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Map created shipments into data objects as required by the shipping module.
     *
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
     *
     * @param ShipmentInterface[] $shipments
     * @return DataObject[]
     */
    public function createShipmentsResponse(array $shipments): array
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
     * Map error messages into error object as required by the shipping module.
     *
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
     *
     * @param string[] $messages
     * @return DataObject[]
     */
    public function createErrorResponse(array $messages): array
    {
        $message = implode(' ', $messages);
        $response = $this->dataObjectFactory->create(['data' => ['errors' => $message]]);
        return [$response];
    }
}
