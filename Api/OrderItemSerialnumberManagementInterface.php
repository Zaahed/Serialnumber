<?php

namespace Zaahed\Serialnumber\Api;

interface OrderItemSerialnumberManagementInterface
{
    /**
     * Set serial numbers for order item.
     *
     * @param int $itemId
     * @param string[] $serialnumbers
     * @return void
     */
    public function setSerialnumbers(int $itemId, array $serialnumbers): void;
}