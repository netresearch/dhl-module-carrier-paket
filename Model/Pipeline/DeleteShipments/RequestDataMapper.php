<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\DeleteShipments;

use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;

class RequestDataMapper
{
    /**
     * Extract the track number (= shipment number) for the SDK request.
     *
     * @param TrackRequestInterface $request The cancellation request
     * @return string
     */
    public function mapRequest(TrackRequestInterface $request): string
    {
        return $request->getTrackNumber();
    }
}
