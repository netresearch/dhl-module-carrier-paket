<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Sdk\Paket\Bcs\Model\CreateShipment\RequestType\ShipmentOrderType;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Request mapper.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.netresearch.de/
 */
interface RequestDataMapperInterface
{
    /**
     * Maps the Magento request object to an SDK request object using the SDK request builder.
     *
     * @param Request $request The shipment request
     *
     * @return object
     */
    public function mapRequest(Request $request);
}
