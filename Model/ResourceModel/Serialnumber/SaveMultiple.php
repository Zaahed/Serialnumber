<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Serialnumber;

use Magento\Framework\App\ResourceConnection;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\Serialnumber;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as SerialnumberResource;

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
            SerialnumberInterface::SERIALNUMBER,
            SerialnumberInterface::SOURCE_ITEM_ID,
            SerialnumberInterface::PURCHASE_PRICE,
            SerialnumberInterface::IS_AVAILABLE
        ]);
        $valuesSql = $this->buildValuesSql($serialnumberItems);
        $onDuplicateSql = $this->buildOnDuplicateSql([
            SerialnumberInterface::SOURCE_ITEM_ID,
            SerialnumberInterface::PURCHASE_PRICE,
            SerialnumberInterface::IS_AVAILABLE
        ]);
        $bind = $this->getSqlBindData($serialnumberItems);

        $insertSql = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s %s',
            $tableName,
            $columnsSql,
            $valuesSql,
            $onDuplicateSql
        );
        $connection->query($insertSql, $bind);
    }

    /**
     * Build columns SQL query.
     *
     * @param array $columns
     * @return string
     */
    private function buildColumnsSql(array $columns): string
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
        $sql = rtrim(str_repeat('(?, ?, ?, ?), ', count($serialnumberItems)), ', ');
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
            $bind[] = $serialnumber->getSerialnumber();
            $bind[] = $serialnumber->getSourceItemId();
            $bind[] = $serialnumber->getPurchasePrice();
            $bind[] = $serialnumber->isAvailable();
        }
        return $bind;
    }

    /**
     * Build SQL query for on duplicate event.
     *
     * @param array $fields
     * @return string
     */
    private function buildOnDuplicateSql(array $fields): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedFields = [];
        foreach ($fields as $field) {
            $processedFields[] = sprintf('%1$s = VALUES(%1$s)', $connection->quoteIdentifier($field));
        }
        $sql = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $processedFields);
        return $sql;
    }
}
