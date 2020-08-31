<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class RemoveConfigDataPatch implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        return;
    }
    /**
     * Remove data that was created during module installation.
     *
     * @return void
     */
    public function revert()
    {
        $defaultConnection = $this->schemaSetup->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $configTable = $this->schemaSetup->getTable('core_config_data', ResourceConnection::DEFAULT_CONNECTION);
        $defaultConnection->delete($configTable, "`path` LIKE 'carriers/dhlpaket/%'");
    }
}
