<?php

namespace Zaahed\Serialnumber\Plugin\Sales\Model\ResourceModel\Order\Shipment\Item;

use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Item;
use Zaahed\Serialnumber\Model\ResourceModel\Shipment\Item\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Shipment\Item\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Shipment\Item\Serialnumber;

class SerialnumbersAttribute
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @param CollectionFactory $collectionFactory
     * @param SaveMultiple $saveMultiple
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        SaveMultiple $saveMultiple
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->saveMultiple = $saveMultiple;
    }

    /**
     * Set serial numbers extension attribute on the object after loading.
     *
     * @param Item $subject
     * @param Item $result
     * @param AbstractModel $object
     * @return Item
     */
    public function afterLoad(Item $subject, Item $result, AbstractModel $object)
    {
        if ($object->getEntityId() === null) {
            return $result;
        }

        /** @var Serialnumber[] $serialnumbers */
        $serialnumbers = $this->collectionFactory
            ->create()
            ->addFilter('item_id', $object->getEntityId())
            ->getItems();
        /** @var ShipmentItemInterface $object */
        $object->getExtensionAttributes()->setSerialnumbers($serialnumbers);

        return $result;
    }

    /**
     * Save serial numbers after saving item.
     *
     * @param Item $subject
     * @param Item $result
     * @param AbstractModel $object
     * @return Item
     */
    public function afterSave(Item $subject, Item $result, AbstractModel $object)
    {
        /** @var ShipmentItemInterface $object */
        if ($object->getEntityId() === null ||
            $object->getExtensionAttributes()->getSerialnumbers() === null) {
            return $result;
        }

        $serialnumbers = $object->getExtensionAttributes()->getSerialnumbers();
        foreach ($serialnumbers as $serialnumber) {
            if ($serialnumber->getItemId() !== $object->getEntityId()) {
                $serialnumber->setItemId($object->getEntityId());
            }
        }

        $this->saveMultiple->execute($serialnumbers);

        return $result;
    }
}