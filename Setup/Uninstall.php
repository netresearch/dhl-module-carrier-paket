<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\Setup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * Remove data that was created during module installation.
     *
     * @param SchemaSetupInterface|Setup $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $defaultConnection = $setup->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $configTable = $setup->getTable('core_config_data');
        $defaultConnection->delete($configTable, "`path` LIKE 'carriers/dhlpaket/%'");
    }
}
