<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Plugin;

use Magento\Config\App\Config\Source\DumpConfigSourceAggregated;

/**
 * UnsetSandboxPaths
 *
 * Sandbox config defaults are static values distributed between environments
 * via config.xml file. There is no need to dump them to the config.php or
 * env.php files. Doing so causes issues when importing them as the necessary
 * backend model is not declared in system.xml
 */
class UnsetSandboxPaths
{
    /**
     * Prevent `account_settings/sandbox_*` settings from being dumped on `app:config:dump` command.
     *
     *
     * @param DumpConfigSourceAggregated $subject
     * @param string[][][][][] $result
     * @return string[][][][][]
     */
    public function afterGet(DumpConfigSourceAggregated $subject, array $result): array
    {
        unset($result['default']['dhlshippingsolutions']['dhlpaket']['account_settings']['sandbox_auth_username']);
        unset($result['default']['dhlshippingsolutions']['dhlpaket']['account_settings']['sandbox_auth_password']);
        unset($result['default']['dhlshippingsolutions']['dhlpaket']['account_settings']['sandbox_username']);
        unset($result['default']['dhlshippingsolutions']['dhlpaket']['account_settings']['sandbox_password']);
        unset($result['default']['dhlshippingsolutions']['dhlpaket']['account_settings']['sandbox_account_number']);
        unset($result['default']['dhlshippingsolutions']['dhlpaket']['account_settings']['sandbox_account_participations']);
        unset($result['default']['dhlshippingsolutions']['dhlpaket']['shipment_defaults']['shipping_products']);

        return $result;
    }
}
