<?php

namespace Zaahed\Serialnumber\Model\Service;

use Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem;
use Zaahed\Serialnumber\Api\OrderItemSerialnumberManagementInterface;

class OrderItemSerialnumberService implements OrderItemSerialnumberManagementInterface
{
    /**
     * @var SetSerialnumbersForOrderItem
     */
    private $setSerialnumbersForOrderItem;

    /**
     * @param SetSerialnumbersForOrderItem $setSerialnumbersForOrderItem
     */
    public function __construct(SetSerialnumbersForOrderItem $setSerialnumbersForOrderItem)
    {
        $this->setSerialnumbersForOrderItem = $setSerialnumbersForOrderItem;
    }

    /**
     * Set serial numbers for order item.
     *
     * @param int $itemId
     * @param string[] $serialnumbers
     * @return void
     */
    public function setSerialnumbers(int $itemId, array $serialnumbers): void
    {
        $this->setSerialnumbersForOrderItem->execute($itemId, $serialnumbers);
    }
}