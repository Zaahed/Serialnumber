<?php

namespace Zaahed\Serialnumber\Api\Data;
interface OrderItemSerialnumberInterface
{
    /**
     * Get item ID.
     *
     * @return int
     */
    public function getItemId(): int;

    /**
     * Set item ID.
     *
     * @param int $itemId
     * @return OrderItemSerialnumberInterface
     */
    public function setItemId(int $itemId): OrderItemSerialnumberInterface;

    /**
     * Get serial number ID.
     *
     * @return int
     */
    public function getSerialnumberId(): int;

    /**
     * Set serial number ID.
     *
     * @param int $serialnumber
     * @return OrderItemSerialnumberInterface
     */
    public function setSerialnumberId(int $serialnumber): OrderItemSerialnumberInterface;
}