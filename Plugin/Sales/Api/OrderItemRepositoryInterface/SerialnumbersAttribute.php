<?php

namespace Zaahed\Serialnumber\Plugin\Sales\Api\OrderItemRepositoryInterface;

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\Order\Item\Serialnumber;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\CollectionFactory;

class SerialnumbersAttribute
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Serial numbers extension attribute.
     *
     * @param OrderItemRepositoryInterface $subject
     * @param OrderItemInterface $item
     * @return OrderItemInterface
     */
    public function afterGet(
        OrderItemRepositoryInterface $subject,
        OrderItemInterface $item
    ) {
        $serialnumbers = $this->getSerialnumbers([$item->getItemId()]);
        $serialnumbers = $serialnumbers[$item->getItemId()] ?? [];
        if (empty($serialnumbers)) {
            return $item;
        }

        $item->getExtensionAttributes()
            ->setSerialnumbers($serialnumbers);

        return $item;
    }

    /**
     * Serial numbers extension attribute.
     *
     * @param OrderItemRepositoryInterface $subject
     * @param OrderItemSearchResultInterface $result
     * @return OrderItemSearchResultInterface
     */
    public function afterGetList(
        OrderItemRepositoryInterface $subject,
        OrderItemSearchResultInterface $result
    ) {
        $itemIds = [];
        foreach ($result->getItems() as $item) {
            $itemIds[] = $item->getItemId();
        }

        $foundSerialnumbers = $this->getSerialnumbers($itemIds);

        foreach ($result->getItems() as $item) {
            $serialnumbers = $foundSerialnumbers[$item->getItemId()] ?? [];
            if (empty($serialnumbers)) {
                continue;
            }

            $item->getExtensionAttributes()->setSerialnumbers($serialnumbers);
        }

        return $result;
    }

    /**
     * Get serial numbers with item IDs as keys.
     *
     * @param array $itemIds
     * @return array
     */
    private function getSerialnumbers(array $itemIds)
    {
        $result = [];

        /** @var Serialnumber[] $items */
        $items = $this->collectionFactory
            ->create()
            ->addFieldToFilter('item_id', ['in' => $itemIds])
            ->join(
                'serialnumber',
                'main_table.serialnumber_id = serialnumber.entity_id',
                SerialnumberInterface::SERIALNUMBER
            )
            ->getItems();

        foreach ($items as $item) {
            $result[$item->getItemId()][] = $item;
        }

        return $result;
    }
}
