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
 * @deprecated
 * @see \Dhl\Paket\Model\Carrier\ApiGateway::createShipmentResponse
 *
 * @inheritDoc
 */
class ResponseDataMapper implements ResponseDataMapperInterface
{
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var LabelGenerator
     */
    private $labelGenerator;

    /**
     * ResponseDataMapper constructor.
     * @param DataObjectFactory $dataObjectFactory
     * @param LabelGenerator $labelGenerator
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        LabelGenerator $labelGenerator
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->labelGenerator = $labelGenerator;
    }

    /**
     * @inheritDoc
     */
    public function mapResult(array $shipmentResponse): DataObject
    {
        $data = array_map(function (ShipmentInterface $package) {
            $labelsContent = [];

            // convert b64 into binary strings
            foreach ($package->getLabels() as $b64LabelData) {
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

            return [
                'tracking_number' => $package->getShipmentNumber(),
                'shipping_label_content' => $shippingLabelContent,
            ];
        }, $shipmentResponse);

        return $this->dataObjectFactory->create(['data' => $data[0]]);
    }
}
