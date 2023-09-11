<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;

class OrderViewCollection extends SearchResult
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinLeft(
                'serialnumber',
                'main_table.serialnumber_id = serialnumber.entity_id',
                SerialnumberInterface::SERIALNUMBER
            )
            ->joinLeft(
                'sales_order_item',
                'main_table.item_id = sales_order_item.item_id',
                ['order_id', 'name', 'product_id']
            );

        $this->addFilterToMap('order_id', 'sales_order_item.order_id');
    }
}
