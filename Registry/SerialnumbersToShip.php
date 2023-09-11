<?php

namespace Zaahed\Serialnumber\Registry;

class SerialnumbersToShip
{
    /**
     * @var array
     */
    private $items;

    /**
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Set serial numbers for an order item.
     *
     * @param int $orderItemId
     * @param array $serialnumberIds
     * @return void
     */
    public function set(int $orderItemId, array $serialnumberIds)
    {
        $this->items[$orderItemId] = $serialnumberIds;
    }

    /**
     * Get serial numbers by order item id.
     *
     * @param $orderItemId
     * @return string[]
     */
    public function get($orderItemId)
    {
        return $this->items[$orderItemId] ?? [];
    }

    /**
     * Add one or more serial numbers.
     *
     * @param int $orderItemId
     * @param string[] $serialnumberIds
     * @return void
     */
    public function add(int $orderItemId, array $serialnumberIds)
    {
        $serialnumberIds = array_merge(
            $this->get($orderItemId),
            $serialnumberIds
        );

        $this->set($orderItemId, $serialnumberIds);
    }

    /**
     * Empty registry.
     *
     * @return void
     */
    public function clean()
    {
        $this->items = [];
    }
}
