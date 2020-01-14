<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\DeleteShipments;

use Dhl\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;

/**
 * Request mapper.
 *
 * @author Rico Sonntag <rico.sonntag@netresearch.de>
 * @link https://www.netresearch.de/
 */
class RequestDataMapper
{

    /**
     * Extract the track number (= shipment number) for the SDK request.
     *
     * @param TrackRequestInterface $request The cancellation request
     * @return string
     */
    public function mapRequest(TrackRequestInterface $request)
    {
        return $request->getTrackNumber();
    }
}
