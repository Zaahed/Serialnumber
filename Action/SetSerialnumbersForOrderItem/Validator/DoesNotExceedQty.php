<?php

namespace Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem\Validator;

use Magento\Sales\Api\OrderItemRepositoryInterface;

class DoesNotExceedQty implements ValidatorInterface
{
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @param OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(OrderItemRepositoryInterface $orderItemRepository)
    {
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * @inheritDoc
     */
    public function validate(int $itemId, array $serialnumbers): array
    {
        $item = $this->orderItemRepository->get($itemId);
        $availableQty = $item->getQtyOrdered() - $item->getQtyCanceled();
        $serialnumberQty = count($serialnumbers);
        if ($serialnumberQty > $availableQty) {
            return [__('Serialnumbers (%1) exceed the available qty (%2).',
                [$serialnumberQty, $availableQty])];
        }

        return [];
    }
}