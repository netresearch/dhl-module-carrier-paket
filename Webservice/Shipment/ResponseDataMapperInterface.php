<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Sdk\Bcs\Api\Data\CreateShipmentOrderResponseInterface;
use Magento\Framework\DataObject;

/**
 * Response mapper.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.netresearch.de/
 */
interface ResponseDataMapperInterface
{
    /**
     * Maps the SDK response object into an Magento response data object.
     *
     * @param CreateShipmentOrderResponseInterface $shipmentResponse The shipment response
     *
     * @return DataObject
     */
    public function mapResult(CreateShipmentOrderResponseInterface $shipmentResponse): DataObject;
}
