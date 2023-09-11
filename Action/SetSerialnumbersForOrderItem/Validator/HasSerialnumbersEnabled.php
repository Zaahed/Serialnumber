<?php

namespace Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem\Validator;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class HasSerialnumbersEnabled implements ValidatorInterface
{
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritDoc
     */
    public function validate(int $itemId, array $serialnumbers): array
    {
        $item = $this->orderItemRepository->get($itemId);
        $productId = $item->getProductId();
        if ($productId === null) {
            return [];
        }

        try {
            $product = $this->productRepository->getById($productId);
            $attribute = $product->getCustomAttribute('no_serialnumber');
            if ($attribute !== null && $attribute->getValue()) {
                return ['Serial numbers are disabled for this item.'];
            }
        } catch (NoSuchEntityException $e) {
        }

        return [];
    }
}