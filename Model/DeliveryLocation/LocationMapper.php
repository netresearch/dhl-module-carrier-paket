<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\DeliveryLocation;

use Dhl\Sdk\LocationFinder\Api\Data\LocationInterface as SdkLocationInterface;
use Dhl\Sdk\LocationFinder\Api\Data\OpeningHoursInterface as SdkOpeningHoursInterface;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\AddressInterface;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\AddressInterfaceFactory;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\LocationInterface;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\LocationInterfaceFactory;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\OpeningHoursInterface;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\OpeningHoursInterfaceFactory;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\TimeFrameInterface;
use Dhl\ShippingCore\Api\Data\DeliveryLocation\TimeFrameInterfaceFactory;

/**
 * Class LocationMapper
 *
 * Map SDK locations into objects suitable for the Shopfinder REST endpoint.
 *
 * @author  Andreas Müller <andreas.mueller@netresearch.de>
 * @link    https://www.netresearch.de
 */
class LocationMapper
{
    const ICON_PACKSTATION_PATH = 'Dhl_Paket::images/icon-packstation.png';
    const ICON_PAKETSHOP_PATH = 'Dhl_Paket::images/icon-parcelshop.png';
    const ICON_POSTFILIALE_PATH = 'Dhl_Paket::images/icon-postoffice.png';

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var ImageUrlProcessor
     */
    private $imageProcessor;

    /**
     * @var TimeFrameInterfaceFactory
     */
    private $timeFrameFactory;

    /**
     * @var OpeningHoursInterfaceFactory
     */
    private $openingHoursFactory;

    /**
     * @var LocationInterfaceFactory
     */
    private $locationFactory;

    /**
     * @var string[]
     */
    private $icons = [
        SdkLocationInterface::TYPE_PACKSTATION => self::ICON_PACKSTATION_PATH,
        SdkLocationInterface::TYPE_PARCELSHOP => self::ICON_PAKETSHOP_PATH,
        SdkLocationInterface::TYPE_POSTOFFICE => self::ICON_POSTFILIALE_PATH,
    ];

    /**
     * LocationMapper constructor.
     *
     * @param ImageUrlProcessor $imageProcessor
     * @param AddressInterfaceFactory $addressFactory
     * @param TimeFrameInterfaceFactory $timeFrameFactory
     * @param OpeningHoursInterfaceFactory $openingHoursFactory
     * @param LocationInterfaceFactory $locationFactory
     */
    public function __construct(
        ImageUrlProcessor $imageProcessor,
        AddressInterfaceFactory $addressFactory,
        TimeFrameInterfaceFactory $timeFrameFactory,
        OpeningHoursInterfaceFactory $openingHoursFactory,
        LocationInterfaceFactory $locationFactory
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->addressFactory = $addressFactory;
        $this->timeFrameFactory = $timeFrameFactory;
        $this->openingHoursFactory = $openingHoursFactory;
        $this->locationFactory = $locationFactory;
    }

    /**
     * @param string $shopType
     * @return string
     */
    private function getMapIconUrl(string $shopType): string
    {
        if (!array_key_exists($shopType, $this->icons)) {
            return '';
        }

        return $this->imageProcessor->getUrl($this->icons[$shopType]);
    }

    /**
     * Translate shopType
     *
     * @param string $shopType
     * @return string
     */
    private function getTypeName(string $shopType): string
    {
        switch ($shopType) {
            case SdkLocationInterface::TYPE_PACKSTATION:
                return __('Parcel Station')->render();
            case SdkLocationInterface::TYPE_PARCELSHOP:
                return __('Parcel Shop')->render();
            case SdkLocationInterface::TYPE_POSTOFFICE:
                return __('Post Office')->render();
            default:
                return '';
        }
    }

    /**
     * Map SDK Address to internal Address object
     *
     * @param SdkLocationInterface $data
     * @return AddressInterface
     */
    private function mapAddress(
        SdkLocationInterface $data
    ): AddressInterface {
        $addressFactory = $this->addressFactory->create();
        $address = $data->getAddress();
        $addressFactory->setCountryCode($address->getCountry());
        $addressFactory->setCity($address->getCity());
        $addressFactory->setStreet(
            implode(' ', [$address->getStreet(), $address->getStreetNumber()])
        );
        $addressFactory->setPostalCode($address->getPostalCode());
        $addressFactory->setCompany($data->getName());

        return $addressFactory;
    }

    /**
     * Set opening hours
     *
     * @param SdkOpeningHoursInterface[] $data
     * @return OpeningHoursInterface[]
     */
    private function mapOpeningHours(array $data): array
    {
        $openingHoursList = [];
        $timeFramesMap = $this->mapTimeFrames($data);
        foreach ($timeFramesMap as $day => $timeFrames) {
            $openingHours = $this->openingHoursFactory->create();
            $openingHours->setTimeFrames($timeFrames);
            $openingHours->setDayOfWeek($this->mapDayOfWeek($day));
            $openingHoursList[] = $openingHours;
        }

        return $openingHoursList;
    }

    /**
     * Map opening hour timeframes to weekdays
     *
     * @param SdkOpeningHoursInterface[] $data
     * @return TimeFrameInterface[][]
     */
    private function mapTimeFrames(array $data): array
    {
        /** @var TimeFrameInterface[][] $timeFrames */
        $timeFramesMap = [];
        foreach ($data as $open) {
            $timeFrame = $this->timeFrameFactory->create();
            $timeFrame->setOpens($open->getOpens());
            $timeFrame->setCloses($open->getCloses());
            $timeFramesMap[$open->getDayOfWeek()][] = $timeFrame;
        }

        // sort days
        ksort($timeFramesMap);

        // sort timeframes
        foreach ($timeFramesMap as $day => $timeFrames) {
            usort(
                $timeFrames,
                static function (TimeFrameInterface $a, TimeFrameInterface $b) {
                    return (float)$a->getOpens() > (float)$b->getOpens();
                }
            );
            $timeFramesMap[$day] = $timeFrames;
        }

        return  $timeFramesMap;
    }

    /**
     * Get weekday from given day number
     *
     * @param int $dayOfWeek
     * @return string
     */
    private function mapDayOfWeek(int $dayOfWeek): string
    {
        switch ($dayOfWeek) {
            case 0:
            case 7:
                return __('Sun')->render();
            case 1:
                return __('Mon')->render();
            case 2:
                return __('Tue')->render();
            case 3:
                return __('Wed')->render();
            case 4:
                return __('Thu')->render();
            case 5:
                return __('Fri')->render();
            case 6:
                return __('Sat')->render();
            default:
                return '';
        }
    }

    /**
     * Map services into a readable sub-selection
     *
     * @param SdkLocationInterface $locationData
     * @return string[]
     */
    private function mapServices(SdkLocationInterface $locationData): array
    {
        $services = [];
        if ($locationData->hasHandicapAccess()) {
            $services[] = __('Disability Access')->render();
        }
        if ($locationData->hasParkingArea()) {
            $services[] = __('Parking available')->render();
        }

        return $services;
    }

    /**
     * Map location data from api to corresponding locations
     *
     * @param SdkLocationInterface[] $apiLocations
     * @return LocationInterface[]
     */
    public function mapLocations(array $apiLocations): array
    {
        $locations = [];

        foreach ($apiLocations as $apiLocation) {
            $location = $this->locationFactory->create();

            $icon = $this->getMapIconUrl($apiLocation->getType());
            $shopType = $this->getTypeName($apiLocation->getType());
            $location->setIcon($icon);
            $location->setShopType($apiLocation->getType());
            $location->setDisplayName($shopType . ' ' . $apiLocation->getNumber());
            $location->setShopNumber($apiLocation->getNumber());
            $location->setShopId($apiLocation->getId());
            $location->setServices($this->mapServices($apiLocation));
            $location->setAddress($this->mapAddress($apiLocation));
            $location->setOpeningHours($this->mapOpeningHours($apiLocation->getOpeningHours()));
            $location->setLatitude($apiLocation->getLatitude());
            $location->setLongitude($apiLocation->getLongitude());
            $locations[] = $location;
        }

        return $locations;
    }
}
