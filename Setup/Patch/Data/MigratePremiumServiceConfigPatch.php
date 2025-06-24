<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigratePremiumServiceConfigPatch implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    #[\Override]
    public static function getDependencies(): array
    {
        return [];
    }

    #[\Override]
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Map Premium service configuration to new Delivery Type config field.
     *
     * @return void
     * @throws \Exception
     */
    #[\Override]
    public function apply()
    {
        $oldPath = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/premium';
        $newPath = 'dhlshippingsolutions/dhlpaket/shipment_defaults/services/delivery_type';

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('core_config_data');

        $cols = [
            'scope' => 'scope',
            'scope_id' => 'scope_id',
            'path' => new \Zend_Db_Expr("'$newPath'"),
            'value' => new \Zend_Db_Expr("CASE WHEN value = 1 then 'PREMIUM' else 'ECONOMY' END")
        ];

        $select = $connection->select()->from($table, $cols)->where('path = ?', $oldPath);
        $query = $connection->insertFromSelect($select, $table, array_keys($cols), AdapterInterface::INSERT_IGNORE);
        $connection->query($query);
        $connection->delete($table, ['path = ?' => $oldPath]);
    }
}
