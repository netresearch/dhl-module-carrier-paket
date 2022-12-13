<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Setup\Patch\Data;

use Dhl\Paket\Model\Config\ModuleConfig;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Netresearch\ShippingCore\Setup\Patch\Data\Migration\Config;

class MigrateAccountSettingsPatch implements DataPatchInterface
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
     * Restructure account data for SOAP and REST API access.
     *
     * phpcs:disable Generic.Files.LineLength.TooLong
     *
     * @return void
     * @throws \Exception
     */
    public function apply()
    {
        $this->config->migrate([
            'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/api_username' => ModuleConfig::CONFIG_PATH_USER,
            'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/api_password' => ModuleConfig::CONFIG_PATH_PASS,
            'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/account_number' => ModuleConfig::CONFIG_PATH_EKP,
            'dhlshippingsolutions/dhlpaket/account_settings/sandboxmode_group/account_participations' => ModuleConfig::CONFIG_PATH_PARTICIPATIONS,
        ]);
    }
}
