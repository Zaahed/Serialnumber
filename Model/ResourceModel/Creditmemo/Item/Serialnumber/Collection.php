<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Creditmemo\Item\Serialnumber;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zaahed\Serialnumber\Model\ResourceModel\Creditmemo\Item\Serialnumber as ResourceModel;
use Zaahed\Serialnumber\Model\Creditmemo\Item\Serialnumber as Model;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'creditmemo_item_serialnumber_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
