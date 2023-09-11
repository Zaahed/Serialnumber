<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log as ResourceModel;
use Zaahed\Serialnumber\Model\Serialnumber\Log as Model;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'serialnumber_log_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
