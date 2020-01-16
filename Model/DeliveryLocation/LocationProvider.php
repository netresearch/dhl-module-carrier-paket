<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\DeliveryLocation;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Webservice\LocationFinderService;
use Dhl\Sdk\LocationFinder\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\AddressInterface;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\LocationInterface;
use Dhl\ShippingCore\Api\DeliveryLocation\LocationProviderInterface;
use Dhl\ShippingCore\Model\Util\StreetSplitter;
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
     * @var LocationFilter
     */
    private $locationFilter;

    /**
     * @var LocationMapper
     */
    private $locationMapper;

    /**
     * @var StreetSplitter
     */
    private $streetSplitter;

    /**
     * LocationProvider constructor.
     *
     * @param LocationFinderService $locationFinderService
     * @param LocationFilter $locationFilter
     * @param LocationMapper $locationMapper
     * @param StreetSplitter $streetSplitter
     */
    public function __construct(
        LocationFinderService $locationFinderService,
        LocationFilter $locationFilter,
        LocationMapper $locationMapper,
        StreetSplitter $streetSplitter
    ) {
        $this->locationFinderService = $locationFinderService;
        $this->locationFilter = $locationFilter;
        $this->locationMapper = $locationMapper;
        $this->streetSplitter = $streetSplitter;
    }

    /**
     * @param AddressInterface $address
     * @return LocationInterface[]
     * @throws LocalizedException
     */
    public function getLocationsByAddress(AddressInterface $address): array
    {
        $street = $this->streetSplitter->splitStreet($address->getStreet());
        try {
            $locationData = $this->locationFinderService->getLocations(
                $address->getCountryCode(),
                $address->getPostalCode(),
                $address->getCity(),
                $street['street_name'],
                $street['street_number']
            );
        } catch (ServiceException $exception) {
            throw new NoSuchEntityException(
                __('There was a problem finding locations. Please check if the address is valid.')
            );
        }

        if (empty($locationData)) {
            throw new NoSuchEntityException(
                __('No locations found for the given address. Please try another address.')
            );
        }

        $locations = $this->locationFilter->removeParcelShops($locationData);

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
