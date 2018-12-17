<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Sdk\Bcs\Api\Data\CreateShipmentOrderResponseInterface;
use Dhl\Sdk\Bcs\Model\Response\CreateShipmentOrder\LabelData;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;

/**
 * @inheritDoc
 */
class ResponseDataMapper implements ResponseDataMapperInterface
{
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * Constructor.
     *
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(DataObjectFactory $dataObjectFactory)
    {
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @inheritDoc
     */
    public function mapResult(CreateShipmentOrderResponseInterface $shipmentResponse): DataObject
    {
        /** @var LabelData $labelData */
        $labelData = $shipmentResponse->getLabelData();
        $labelData = reset($labelData);

        return $this->dataObjectFactory->create([
            'data' => [
                'tracking_number'        => $labelData->getShipmentNumber(),
                'shipping_label_content' => $labelData->getLabelData(),
            ],
        ]);
    }
}
