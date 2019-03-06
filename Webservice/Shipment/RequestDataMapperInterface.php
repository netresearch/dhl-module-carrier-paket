<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Magento\Shipping\Model\Shipment\Request;

/**
 * Request mapper.
 *
 * @deprecated | convert shipment request to shipment order using an "extractor" in DHL shipping core.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
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
