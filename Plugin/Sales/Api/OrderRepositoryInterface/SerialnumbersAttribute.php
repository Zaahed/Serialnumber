<?php

declare(strict_types=1);


namespace Zaahed\Serialnumber\Plugin\Sales\Api\OrderRepositoryInterface;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
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
     * Add serial numbers to order items.
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        $itemIds = [];
        foreach ($order->getItems() as $item) {
            $itemIds[] = $item->getItemId();
        }

        $foundSerialnumbers = $this->getSerialnumbers($itemIds);

        foreach ($order->getItems() as $item) {
            $serialnumbers = $foundSerialnumbers[$item->getItemId()] ?? [];
            if (empty($serialnumbers)) {
                continue;
            }

            $item->getExtensionAttributes()->setSerialnumbers($serialnumbers);
        }

        return $order;
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
