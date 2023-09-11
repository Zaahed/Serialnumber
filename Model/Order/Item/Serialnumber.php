<?php

namespace Zaahed\Serialnumber\Model\Order\Item;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterfaceFactory;
use Zaahed\Serialnumber\Api\Data\OrderItemSerialnumberInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber as ResourceModel;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as SerialnumberResource;

class Serialnumber extends AbstractModel implements OrderItemSerialnumberInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'order_item_serialnumber_model';

    /**
     * @var SerialnumberResource
     */
    private $serialnumberResource;

    /**
     * @var SerialnumberInterfaceFactory
     */
    private $serialnumberFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param SerialnumberResource $serialnumberResource
     * @param SerialnumberInterfaceFactory $serialnumberFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        SerialnumberResource $serialnumberResource,
        SerialnumberInterfaceFactory $serialnumberFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context,
            $registry,
            $resource,
            $resourceCollection,
            $data);

        $this->serialnumberResource = $serialnumberResource;
        $this->serialnumberFactory = $serialnumberFactory;
    }

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
     * @return int
     */
    public function getItemId(): int
    {
        return $this->getData('item_id');
    }

    /**
     * Set item ID.
     *
     * @param int $itemId
     * @return OrderItemSerialnumberInterface
     */
    public function setItemId(int $itemId): OrderItemSerialnumberInterface
    {
        $this->setData('item_id', $itemId);
        return $this;
    }

    /**
     * Get serial number ID.
     *
     * @return int
     */
    public function getSerialnumberId(): int
    {
        return $this->getData('serialnumber_id');
    }

    /**
     * Set serial number ID.
     *
     * @param int $serialnumber
     * @return OrderItemSerialnumberInterface
     */
    public function setSerialnumberId(int $serialnumber): OrderItemSerialnumberInterface
    {
        $this->setData('serialnumber_id', $serialnumber);
        return $this;
    }

    /**
     * Get serial number.
     *
     * @return string
     */
    public function getSerialnumber(): string
    {
        if ($this->getData('serialnumber') === null) {
            $serialnumber = $this->serialnumberFactory->create();
            $this->serialnumberResource->load(
                $serialnumber,
                $this->getSerialnumberId()
            );
            $this->setData('serialnumber', $serialnumber->getSerialnumber());
        }

        return $this->getData('serialnumber');
    }
}
