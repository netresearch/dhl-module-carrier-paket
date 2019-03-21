<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Magento\Framework\DataObject;

/**
 * Response mapper.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
interface ResponseDataMapperInterface
{
    /**
     * Maps the SDK response object into an Magento response data object.
     *
     * @param ShipmentInterface[] $shipmentResponse The shipment response
     *
     * @return DataObject
     */
    public function mapResult(array $shipmentResponse): DataObject;
}
