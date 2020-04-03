<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\DeliveryLocation;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Webservice\LocationFinderService;
use Dhl\Sdk\UnifiedLocationFinder\Api\Data\LocationInterface as ApiLocation;
use Dhl\Sdk\UnifiedLocationFinder\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\AddressInterface;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\LocationInterface;
use Dhl\ShippingCore\Api\DeliveryLocation\LocationProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class LocationProvider
 *
 * Handles communication with LocationFinder SDK and transforms results into internal format.
 *
 * @author Andreas Müller <andreas.mueller@netresearch.de>
 * @link   https://www.netresearch.de/
 */
class LocationProvider implements LocationProviderInterface
{
    /**
     * @var LocationFinderService
     */
    private $locationFinderService;

    /**
     * @var LocationMapper
     */
    private $locationMapper;

    /**
     * LocationProvider constructor.
     *
     * @param LocationFinderService $locationFinderService
     * @param LocationMapper $locationMapper
     */
    public function __construct(
        LocationFinderService $locationFinderService,
        LocationMapper $locationMapper
    ) {
        $this->locationFinderService = $locationFinderService;
        $this->locationMapper = $locationMapper;
    }

    /**
     * Obtain pickup locations for given address entity.
     *
     * - Fetch pickup locations via SDK
     * - Remove location types which cannot be used for label creation
     * - Augment data for map display
     *
     * @param AddressInterface $address
     * @return LocationInterface[]
     * @throws LocalizedException
     */
    public function getLocationsByAddress(AddressInterface $address): array
    {
        try {
            $locations = $this->locationFinderService->getPickUpLocations(
                $address->getCountryCode(),
                $address->getPostalCode(),
                $address->getCity(),
                $address->getStreet()
            );
        } catch (ServiceException $exception) {
            throw new NoSuchEntityException(
                __('There was a problem finding locations. Please check if the address is valid.')
            );
        }

        $locations = array_filter(
            $locations,
            static function (ApiLocation $location) {
                return ($location->getType() !== ApiLocation::TYPE_SERVICEPOINT) && $location->getNumber();
            }
        );

        if (empty($locations)) {
            throw new NoSuchEntityException(
                __('No locations found for the given address. Please try another address.')
            );
        }

        return $this->locationMapper->mapLocations($locations);
    }

    /**
     * @return string
     */
    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }
}
