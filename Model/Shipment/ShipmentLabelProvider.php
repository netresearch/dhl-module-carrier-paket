<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Shipment;

use Dhl\Paket\Webservice\ShipmentAdapterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class ShipmentLabelProvider
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ShipmentLabelProvider
{
    /**
     * @var ShipmentAdapterInterface
     */
    private $shipmentAdapter;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * Constructor.
     *
     * @param ShipmentAdapterInterface $shipmentAdapter
     * @param DataObjectFactory        $dataObjectFactory
     */
    public function __construct(
        ShipmentAdapterInterface $shipmentAdapter,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->shipmentAdapter   = $shipmentAdapter;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @param Request $request
     *
     * @return DataObject
     */
    public function getShipmentLabel(Request $request)
    {
        if (!$request->hasData('master_tracking_id')) {
            return $this->shipmentAdapter->getShipmentLabel($request);
        }

        return $this->dataObjectFactory->create();
    }
}
