<?php

namespace Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem\Validator;

use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class HasValidOrderStatus implements ValidatorInterface
{
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var array
     */
    private $allowedStates;

    /**
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param array $allowedStates
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        array $allowedStates = []
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->allowedStates = $allowedStates;
    }

    /**
     * @inheritDoc
     */
    public function validate(int $itemId, array $serialnumbers): array
    {
        $item = $this->orderItemRepository->get($itemId);
        $order = $this->orderRepository->get($item->getOrderId());

        return in_array($order->getState(), $this->allowedStates) ? [] :
            [
                __("Cannot set serial number(s) for item %1 because order state is %2",
                    [$itemId, $order->getState()])
            ];

    }
}