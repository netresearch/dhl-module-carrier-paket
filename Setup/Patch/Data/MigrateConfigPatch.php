<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Setup\Patch\Data;

use Dhl\Paket\Model\Config\ModuleConfig;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Netresearch\ShippingCore\Model\Config\ParcelProcessingConfig;
use Netresearch\ShippingCore\Setup\Patch\Data\Migration\Config;

class MigrateConfigPatch implements DataPatchInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * Migrate config values from dhl/module-carrier-paket version 1.
     *
     * phpcs:disable Generic.Files.LineLength.TooLong
     *
     * @return void
     * @throws \Exception
     */
    public function apply()
    {
        $this->config->migrate([
            'dhlshippingsolutions/dhlpaket/shipment_defaults/cod_reason_for_payment' => ParcelProcessingConfig::CONFIG_PATH_COD_REASON_FOR_PAYMENT,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/customs_reference_numbers' => ModuleConfig::CONFIG_PATH_CUSTOMS_REFERENCE_NUMBERS,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/place_of_committal' => ModuleConfig::CONFIG_PATH_PLACE_OF_COMMITTAL,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/electronic_export_notification' => ModuleConfig::CONFIG_PATH_ELECTRONIC_EXPORT_NOTIFICATION,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/visual_check_of_age' => ModuleConfig::CONFIG_PATH_VISUAL_CHECK_OF_AGE,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/return_shipment' => ModuleConfig::CONFIG_PATH_RETURN_SHIPMENT,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/additional_insurance' => ModuleConfig::CONFIG_PATH_ADDITIONAL_INSURANCE,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/bulky_goods' => ModuleConfig::CONFIG_PATH_BULKY_GOODS,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/parcel_outlet' => ModuleConfig::CONFIG_PATH_PARCEL_OUTLET,
            'dhlshippingsolutions/dhlpaket/shipment_defaults/services_group/parcel_outlet_notification_email' => ModuleConfig::CONFIG_PATH_PARCEL_OUTLET_NOTIFICATION_EMAIL,
            'dhlshippingsolutions/dhlpaket/additional_services/services_group/parcelannouncement' => ModuleConfig::CONFIG_PATH_PARCEL_ANNOUNCEMENT,
            'dhlshippingsolutions/dhlpaket/additional_services/services_group/parcelshopFinder' => ModuleConfig::CONFIG_PATH_PARCEL_STATION_DELIVERY,
            'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredlocation' => ModuleConfig::CONFIG_PATH_PREFERRED_LOCATION,
            'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredneighbour' => ModuleConfig::CONFIG_PATH_PREFERRED_NEIGHBOR,
            'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredday' => ModuleConfig::CONFIG_PATH_PREFERRED_DAY,
            'dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredDayCharge' => ModuleConfig::CONFIG_PATH_PREFERRED_DAY_CHARGE,
        ]);
    }
}
