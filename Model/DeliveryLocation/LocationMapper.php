<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\DeliveryLocation;

use Dhl\Sdk\UnifiedLocationFinder\Api\Data\LocationInterface as SdkLocationInterface;
use Dhl\Sdk\UnifiedLocationFinder\Api\Data\OpeningHoursInterface as SdkOpeningHoursInterface;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\AddressInterface;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\AddressInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\LocationInterface;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\LocationInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\OpeningHoursInterface;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\OpeningHoursInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\TimeFrameInterface;
use Netresearch\ShippingCore\Api\Data\DeliveryLocation\TimeFrameInterfaceFactory;
use Netresearch\ShippingCore\Api\Util\AssetUrlInterface;

/**
 * Map SDK locations into objects suitable for the Shopfinder REST endpoint.
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
     * @var AssetUrlInterface
     */
    private $assetUrl;

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
        SdkLocationInterface::TYPE_LOCKER => self::ICON_PACKSTATION_PATH,
        SdkLocationInterface::TYPE_POSTBANK => self::ICON_POSTFILIALE_PATH,
        SdkLocationInterface::TYPE_POSTOFFICE => self::ICON_POSTFILIALE_PATH,
        SdkLocationInterface::TYPE_SERVICEPOINT => self::ICON_PAKETSHOP_PATH,
    ];

    public function __construct(
        AssetUrlInterface $assetUrl,
        AddressInterfaceFactory $addressFactory,
        TimeFrameInterfaceFactory $timeFrameFactory,
        OpeningHoursInterfaceFactory $openingHoursFactory,
        LocationInterfaceFactory $locationFactory
    ) {
        $this->assetUrl = $assetUrl;
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

        return $this->assetUrl->get($this->icons[$shopType]);
    }

    /**
     * Translate shopType
     *
     * @param string $shopType
     * @param string $number
     * @return string
     */
    private function getDisplayName(string $shopType, string $number): string
    {
        switch ($shopType) {
            case SdkLocationInterface::TYPE_LOCKER:
                return __('Parcel Station %1', $number)->render();
            case SdkLocationInterface::TYPE_POSTBANK:
                return __('Post Bank %1', $number)->render();
            case SdkLocationInterface::TYPE_POSTOFFICE:
                return __('Post Office %1', $number)->render();
            case SdkLocationInterface::TYPE_SERVICEPOINT:
                return __('Parcel Shop %1', $number)->render();
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
        $address = $this->addressFactory->create();
        $ApiAddress = $data->getAddress();
        $address->setCountryCode($ApiAddress->getCountryCode());
        $address->setCity($ApiAddress->getCity());
        $address->setStreet($ApiAddress->getStreet());
        $address->setPostalCode($ApiAddress->getPostalCode());
        $address->setCompany($data->getType() !== SdkLocationInterface::TYPE_LOCKER ? $data->getName() : '');

        return $address;
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
            $day = str_replace('http://schema.org/', '', $day);
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
            $timeFrame->setOpens(substr($open->getOpens(), 0, 5));
            $timeFrame->setCloses(substr($open->getCloses(), 0, 5));
            $timeFramesMap[$open->getDayOfWeek()][] = $timeFrame;
        }

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
     * @param string $dayOfWeek
     * @return string
     */
    private function mapDayOfWeek(string $dayOfWeek): string
    {
        switch ($dayOfWeek) {
            case 'Sunday':
                return __('Sun')->render();
            case 'Monday':
                return __('Mon')->render();
            case 'Tuesday':
                return __('Tue')->render();
            case 'Wednesday':
                return __('Wed')->render();
            case 'Thursday':
                return __('Thu')->render();
            case 'Friday':
                return __('Fri')->render();
            case 'Saturday':
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
        if (in_array('handicapped-access', $locationData->getServices(), true)) {
            $services[] = __('Disability Access')->render();
        }
        if (in_array('parking', $locationData->getServices(), true)) {
            $services[] = __('Parking available')->render();
        }

        return $services;
    }

    /**
     * Map location data from api to corresponding locations
     * @param SdkLocationInterface[] $apiLocations
     * @return LocationInterface[]
     */
    public function mapLocations(array $apiLocations): array
    {
        $locations = [];

        foreach ($apiLocations as $apiLocation) {
            $location = $this->locationFactory->create();
            $icon = $this->getMapIconUrl($apiLocation->getType());
            $location->setIcon($icon);
            $shopType = ($apiLocation->getType() === SdkLocationInterface::TYPE_POSTBANK)
                ? SdkLocationInterface::TYPE_POSTOFFICE
                : $apiLocation->getType();
            $location->setShopType($shopType);
            $location->setDisplayName($this->getDisplayName($apiLocation->getType(), $apiLocation->getNumber()));
            $location->setShopNumber($apiLocation->getNumber());
            $location->setShopId($apiLocation->getId());
            $location->setServices($this->mapServices($apiLocation));
            $location->setAddress($this->mapAddress($apiLocation));
            $openingHours = ($apiLocation->getType() === SdkLocationInterface::TYPE_LOCKER) ?
                [] : $this->mapOpeningHours($apiLocation->getOpeningHours());
            $location->setOpeningHours($openingHours);
            $location->setLatitude($apiLocation->getGeo()->getLat());
            $location->setLongitude($apiLocation->getGeo()->getLong());
            $locations[] = $location;
        }

        return $locations;
    }
}
