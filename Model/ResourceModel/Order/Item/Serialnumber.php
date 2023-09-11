<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Order\Item;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Serialnumber extends AbstractDb
{
    const TABLE_NAME = 'sales_order_item_serialnumber';

    /**
     * @var string
     */
    protected $_eventPrefix = 'order_item_serialnumber_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(static::TABLE_NAME, 'id');
        $this->_useIsObjectNew = true;
    }
}
