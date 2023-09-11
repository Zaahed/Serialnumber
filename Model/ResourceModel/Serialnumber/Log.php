<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Serialnumber;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Log extends AbstractDb
{
    const TABLE_NAME = 'serialnumber_log';

    /**
     * @var string
     */
    protected $_eventPrefix = 'serialnumber_log_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(static::TABLE_NAME, 'id');
        $this->_useIsObjectNew = true;
    }
}