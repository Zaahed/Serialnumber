<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Shipment\Item\Serialnumber\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param null|string $resourceModel
     * @param null|string $identifierName
     * @param null|string $connectionName
     * @throws LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy,
            $eventManager, $mainTable, $resourceModel, $identifierName,
            $connectionName);
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinLeft(
            'sales_shipment_item',
            'main_table.item_id = sales_shipment_item.entity_id',
                ['sku',
                 'name',
                 'parent_id',
                 'product_id',
                 'order_item_id']
            )
            ->joinLeft(
                'sales_shipment',
                'sales_shipment_item.parent_id = sales_shipment.entity_id',
                ['entity_id AS shipment_id',
                 'order_id',
                 'increment_id as shipment_increment_id']
            )
            ->joinLeft(
                'sales_order',
                'sales_shipment.order_id = sales_order.entity_id',
                ['increment_id as order_increment_id']
            );

        $this->addFilterToMap('sku', 'sales_shipment_item.sku');
        $this->addFilterToMap('name', 'sales_shipment_item.name');
        $this->addFilterToMap('order_increment_id', 'sales_order.increment_id');
        $this->addFilterToMap('shipment_increment_id', 'sales_shipment.increment_id');
    }
}