<?php

namespace Zaahed\Serialnumber\Model\Order\Item;

use Magento\Framework\Exception\NoSuchEntityException;
use Zaahed\Serialnumber\Api\Data\OrderItemSerialnumberInterface;
use Zaahed\Serialnumber\Api\Data\OrderItemSerialnumberInterfaceFactory as OrderItemSerialnumberFactory;
use Zaahed\Serialnumber\Api\OrderItemSerialnumberRepositoryInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber as Resource;

class SerialnumberRepository implements OrderItemSerialnumberRepositoryInterface
{
    /**
     * @var OrderItemSerialnumberFactory
     */
    private $orderItemSerialnumberFactory;

    /**
     * @var Resource
     */
    private $resource;

    /**
     * @param OrderItemSerialnumberFactory $orderItemSerialnumberFactory
     * @param Resource $resource
     */
    public function __construct(
        OrderItemSerialnumberFactory $orderItemSerialnumberFactory,
        Resource $resource
    ) {
        $this->orderItemSerialnumberFactory = $orderItemSerialnumberFactory;
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function get(int $orderItemSerialnumberId): OrderItemSerialnumberInterface
    {
        $orderItemSerialnumber = $this->orderItemSerialnumberFactory->create();
        $this->resource->load($orderItemSerialnumber, $orderItemSerialnumberId);
        if ($orderItemSerialnumber->getId() === null) {
            throw new NoSuchEntityException(
                __('Order item serialnumber with id %1 does not exist.', [$orderItemSerialnumberId])
            );
        }

        return $orderItemSerialnumber;
    }

    /**
     * @inheritDoc
     */
    public function save(OrderItemSerialnumberInterface $orderItemSerialnumber)
    {
        $this->resource->save($orderItemSerialnumber);
    }
}