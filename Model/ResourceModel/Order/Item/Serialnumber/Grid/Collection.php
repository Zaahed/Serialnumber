<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinLeft(
            'sales_order_item',
            'main_table.item_id = sales_order_item.item_id',
                ['sku',
                 'name',
                 'order_id',
                 'product_id',
                 'price_incl_tax',
                 'base_price_incl_tax']
            )
            ->joinLeft(
                'sales_order',
                'sales_order_item.order_id = sales_order.entity_id',
                ['increment_id', 'store_name']
            );

        $this->addFilterToMap('sku', 'sales_order_item.sku');
        $this->addFilterToMap('name', 'sales_order_item.name');
        $this->addFilterToMap('increment_id', 'sales_order.increment_id');
    }
}