<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log;

use Magento\Framework\App\ResourceConnection;
use Zaahed\Serialnumber\Model\Serialnumber\Log;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log as LogResource;

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
     * Save multiple log entries.
     *
     * @param Log[] $logEntries
     * @return void
     */
    public function execute(array $logEntries)
    {
        if (!count($logEntries)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(LogResource::TABLE_NAME);

        $columnsSql = $this->buildColumnsSql([
            'serialnumber_id',
            'message',
            'message_values',
            'user_id'
        ]);
        $valuesSql = $this->buildValuesSql($logEntries);
        $bind = $this->getSqlBindData($logEntries);

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
     * @param Log[] $logEntries
     * @return string
     */
    private function buildValuesSql(array $logEntries): string
    {
        $sql = rtrim(str_repeat('(?, ?, ?, ?), ', count($logEntries)), ', ');
        return $sql;
    }

    /**
     * Get SQL bind data.
     *
     * @param Log[] $logEntries
     * @return array
     */
    private function getSqlBindData(array $logEntries): array
    {
        $bind = [];
        foreach ($logEntries as $logEntry) {
            $bind[] = $logEntry->getSerialnumberId();
            $bind[] = $logEntry->getMessageString();
            $bind[] = $logEntry->getMessageValuesJson();
            $bind[] = $logEntry->getUserId();
        }
        return $bind;
    }

}