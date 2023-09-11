<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Serialnumber;

use Magento\Framework\App\ResourceConnection;
use Zaahed\Serialnumber\Model\Serialnumber;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as SerialnumberResource;

class DeleteMultiple
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
     * Delete multiple serial numbers.
     *
     * @param Serialnumber[] $serialnumbers
     * @return void
     */
    public function execute(array $serialnumbers)
    {
        if (!count($serialnumbers)) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SerialnumberResource::TABLE_NAME);

        $whereSql = $this->buildWhereSqlPart($serialnumbers);
        $connection->delete($tableName, $whereSql);
    }

    /**
     * Build WHERE part of SQL query.
     *
     * @param Serialnumber[] $serialnumbers
     * @return string
     */
    private function buildWhereSqlPart(array $serialnumbers): string
    {
        $connection = $this->resourceConnection->getConnection();

        $condition = [];
        foreach ($serialnumbers as $serialnumber) {

            $entityIdCondition = $connection->quoteInto(
                'entity_id' . ' = ?',
                $serialnumber->getEntityId()
            );

            $condition[] = '(' . $entityIdCondition . ')';
        }

        return implode(' OR ', $condition);
    }
}