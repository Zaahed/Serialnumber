<?php

namespace Zaahed\Serialnumber\Api;

use Magento\Framework\Exception\AlreadyExistsException;
use Zaahed\Serialnumber\Api\Data\OrderItemSerialnumberInterface;

interface OrderItemSerialnumberRepositoryInterface
{
    /**
     * Get order item serial number by order item serial number ID.
     *
     * @param int $orderItemSerialnumberId
     * @return OrderItemSerialnumberInterface
     */
    public function get(int $orderItemSerialnumberId): OrderItemSerialnumberInterface;

    /**
     * Save order item serial number.
     *
     * @param OrderItemSerialnumberInterface $orderItemSerialnumber
     * @return void
     * @throws AlreadyExistsException
     */
    public function save(OrderItemSerialnumberInterface $orderItemSerialnumber);
}