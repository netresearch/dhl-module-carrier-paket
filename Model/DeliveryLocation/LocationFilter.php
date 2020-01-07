<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\DeliveryLocation;

use Dhl\Sdk\LocationFinder\Api\Data\LocationInterface as ApiLocation;

/**
 * Class LocationFilter
 *
 * @author  Andreas Müller <andreas.mueller@netresearch.de>
 * @link    https://www.netresearch.de
 */
class LocationFilter
{
    /**
     * Remove locations of type parcel shop, they are not supported by the Label API
     *
     * @param ApiLocation[] $locations
     * @return ApiLocation[]
     */
    public function removeParcelShops($locations): array
    {
        return array_filter(
            $locations,
            static function ($location) {
                /** @var ApiLocation $location */
                return $location->getType() !== ApiLocation::TYPE_PARCELSHOP;
            }
        );
    }
}
