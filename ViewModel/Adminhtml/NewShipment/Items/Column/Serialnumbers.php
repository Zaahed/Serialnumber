<?php

namespace Zaahed\Serialnumber\ViewModel\Adminhtml\NewShipment\Items\Column;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\LineItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Zaahed\Serialnumber\Action\GetShippableSerialnumbersByOrderItemId;
use Zaahed\Serialnumber\Action\IsSerialnumbersDisabledForOrderItem;

class Serialnumbers implements ArgumentInterface
{
    /**
     * @var GetShippableSerialnumbersByOrderItemId
     */
    private $getShippableSerialnumbersByOrderItemId;

    /**
     * @var IsSerialnumbersDisabledForOrderItem
     */
    private $isSerialnumbersDisabledForOrderItem;

    /**
     * @param GetShippableSerialnumbersByOrderItemId $getShippableSerialnumbersByOrderItemId
     * @param IsSerialnumbersDisabledForOrderItem $isSerialnumbersDisabledForOrderItem
     */
    public function __construct(
        GetShippableSerialnumbersByOrderItemId $getShippableSerialnumbersByOrderItemId,
        IsSerialnumbersDisabledForOrderItem $isSerialnumbersDisabledForOrderItem
    ) {
        $this->getShippableSerialnumbersByOrderItemId
            = $getShippableSerialnumbersByOrderItemId;
        $this->isSerialnumbersDisabledForOrderItem
            = $isSerialnumbersDisabledForOrderItem;
    }

    /**
     * Get order item serial numbers available for shipping.
     *
     * @param OrderItemInterface $item
     * @return array
     */
    public function getAvailableSerialnumbers(OrderItemInterface $item): array
    {
        $orderItemId = $item->getItemId();
        return $this->getShippableSerialnumbersByOrderItemId->execute($orderItemId);
    }

    /**
     * Check if serial numbers are enabled for item.
     *
     * @param OrderItemInterface $item
     * @return bool
     */
    public function isDisabled(OrderItemInterface $item): bool
    {
        return $this->isSerialnumbersDisabledForOrderItem
            ->execute($item->getItemId());
    }
}