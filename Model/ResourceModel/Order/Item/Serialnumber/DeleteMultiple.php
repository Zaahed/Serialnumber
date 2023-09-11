<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber;

use Magento\Framework\App\ResourceConnection;
use Zaahed\Serialnumber\Model\Order\Item\Serialnumber;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber as SerialnumberResource;

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
     * Delete multiple order item serial numbers.
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
            $itemIdCondition = $connection->quoteInto(
                'item_id' . ' = ?',
                $serialnumber->getItemId()
            );

            $serialnumberIdCondition = $connection->quoteInto(
                'serialnumber_id' . ' = ?',
                $serialnumber->getSerialnumberId()
            );

            $condition[] = '(' . $itemIdCondition . ' AND ' . $serialnumberIdCondition . ')';
        }

        return implode(' OR ', $condition);
    }
}