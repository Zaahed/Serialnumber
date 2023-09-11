<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Shipment\Item;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Serialnumber extends AbstractDb
{
    const TABLE_NAME = 'sales_shipment_item_serialnumber';

    /**
     * @var string
     */
    protected $_eventPrefix = 'shipment_item_serialnumber_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(static::TABLE_NAME, 'entity_id');
        $this->_useIsObjectNew = true;
    }
}
