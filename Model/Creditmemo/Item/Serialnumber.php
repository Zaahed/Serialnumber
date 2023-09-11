<?php

namespace Zaahed\Serialnumber\Model\Creditmemo\Item;

use Magento\Framework\Model\AbstractModel;
use Zaahed\Serialnumber\Model\ResourceModel\Creditmemo\Item\Serialnumber as ResourceModel;

class Serialnumber extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'creditmemo_item_serialnumber_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get item ID.
     *
     * @return int|null
     */
    public function getItemId(): ?int
    {
        return $this->getData('item_id');
    }

    /**
     * Set item ID.
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): self
    {
        $this->setData('item_id', $itemId);
        return $this;
    }

    /**
     * Get serial number ID.
     *
     * @return int|null
     */
    public function getSerialnumberId(): ?int
    {
        return $this->getData('serialnumber_id');
    }

    /**
     * Set serial number ID.
     *
     * @param int $serialnumber
     * @return $this
     */
    public function setSerialnumberId(int $serialnumber): self
    {
        $this->setData('serialnumber_id', $serialnumber);
        return $this;
    }
}
