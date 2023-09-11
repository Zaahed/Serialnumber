<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Shipment\Item\Serialnumber;

use Magento\Framework\App\ResourceConnection;
use Zaahed\Serialnumber\Model\Shipment\Item\Serialnumber;
use Zaahed\Serialnumber\Model\ResourceModel\Shipment\Item\Serialnumber as SerialnumberResource;

class SaveMultiple
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Save multiple serial number items.
     *
     * @param Serialnumber[] $serialnumberItems
     * @return void
     */
    public function execute(array $serialnumberItems)
    {
        if (!count($serialnumberItems)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SerialnumberResource::TABLE_NAME);

        $columnsSql = $this->buildColumnsSql([
            'item_id',
            'serialnumber_id',
            'created_at'
        ]);
        $valuesSql = $this->buildValuesSql($serialnumberItems);
        $bind = $this->getSqlBindData($serialnumberItems);

        $insertSql = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s',
            $tableName,
            $columnsSql,
            $valuesSql
        );
        $connection->query($insertSql, $bind);
    }

    /**
     * Build columns SQL query.
     *
     * @param array $columns
     * @return string
     */
    public function buildColumnsSql(array $columns): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedColumns = array_map([$connection, 'quoteIdentifier'], $columns);
        $sql = implode(', ', $processedColumns);
        return $sql;
    }

    /**
     * Build values SQL query.
     *
     * @param Serialnumber[] $serialnumberItems
     * @return string
     */
    private function buildValuesSql(array $serialnumberItems): string
    {
        $sql = rtrim(str_repeat('(?, ?, ?), ', count($serialnumberItems)), ', ');
        return $sql;
    }

    /**
     * Get SQL bind data.
     *
     * @param Serialnumber[] $serialnumberItems
     * @return array
     */
    private function getSqlBindData(array $serialnumberItems): array
    {
        $bind = [];
        foreach ($serialnumberItems as $serialnumber) {
            $bind[] = $serialnumber->getItemId();
            $bind[] = $serialnumber->getSerialnumberId();
            $bind[] = $serialnumber->getCreatedAt() ?? date("Y-m-d H:i:s");
        }
        return $bind;
    }

}