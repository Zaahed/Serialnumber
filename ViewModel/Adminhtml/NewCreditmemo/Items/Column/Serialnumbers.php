<?php

namespace Zaahed\Serialnumber\ViewModel\Adminhtml\NewCreditmemo\Items\Column;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Zaahed\Serialnumber\Action\GetRefundableSerialnumbersByOrderItemId;

class Serialnumbers implements ArgumentInterface
{
    /**
     * @var GetRefundableSerialnumbersByOrderItemId
     */
    private $getRefundableSerialnumbersByOrderItemId;

    /**
     * @param GetRefundableSerialnumbersByOrderItemId $getRefundableSerialnumbersByOrderItemId
     */
    public function __construct(
        GetRefundableSerialnumbersByOrderItemId $getRefundableSerialnumbersByOrderItemId
    ) {
        $this->getRefundableSerialnumbersByOrderItemId
            = $getRefundableSerialnumbersByOrderItemId;
    }

    /**
     * Get order item serial numbers available for credit memo.
     *
     * @param OrderItemInterface $item
     * @return array
     */
    public function getAvailableSerialnumbers(OrderItemInterface $item)
    {
        $orderItemId = $item->getItemId();
        return $this->getRefundableSerialnumbersByOrderItemId->execute($orderItemId);
    }
}