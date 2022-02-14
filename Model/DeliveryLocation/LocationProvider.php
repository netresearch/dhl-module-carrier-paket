<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\DeliveryLocation;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Webservice\LocationFinderServiceFactory;
use Dhl\Sdk\UnifiedLocationFinder\Api\Data\LocationInterface as ApiLocation;
use Dhl\Sdk\UnifiedLocationFinder\Api\LocationFinderServiceInterface;
use Dhl\Sdk\UnifiedLocationFinder\Exception\ServiceException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\AddressInterface;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\LocationInterface;
use Netresearch\ShippingCore\Api\DeliveryLocation\LocationProviderInterface;

/**
 * Handles communication with LocationFinder SDK and transforms results into internal format.
 */
class LocationProvider implements LocationProviderInterface
{
    /**
     * @var LocationFinderServiceFactory
     */
    private $locationFinderServiceFactory;

    /**
     * @var LocationMapper
     */
    private $locationMapper;

    public function __construct(
        LocationFinderServiceFactory $locationFinderServiceFactory,
        LocationMapper $locationMapper
    ) {
        $this->locationFinderServiceFactory = $locationFinderServiceFactory;
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
            $service = $this->locationFinderServiceFactory->create();
            $locations = $service->getPickUpLocations(
                $address->getCountryCode(),
                $address->getPostalCode(),
                $address->getCity(),
                $address->getStreet(),
                LocationFinderServiceInterface::SERVICE_PARCEL,
                15000,
                50
            );
        } catch (ServiceException $exception) {
            throw new NoSuchEntityException(
                __('There was a problem finding locations. Please check if the address is valid.')
            );
        }

        $locations = array_filter(
            $locations,
            static function (ApiLocation $location) {
                return (bool) $location->getNumber();
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
