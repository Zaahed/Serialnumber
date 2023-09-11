<?php

namespace Zaahed\Serialnumber\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Serialnumber extends AbstractDb
{
    const TABLE_NAME = 'serialnumber';

    /**
     * @var string
     */
    protected $_eventPrefix = 'serialnumber_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(static::TABLE_NAME, 'entity_id');
        $this->_useIsObjectNew = true;
    }
}
