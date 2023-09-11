<?php

namespace Zaahed\Serialnumber\Action;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class IsSerialnumbersDisabledForOrderItem
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
     * Check if serial numbers are disabled for order item.
     *
     * @param int $itemId
     * @return bool
     */
    public function execute(int $itemId): bool
    {
        $item = $this->orderItemRepository->get($itemId);
        $productId = $item->getProductId();
        if ($productId === null) {
            return false;
        }

        try {
            $product = $this->productRepository->getById($productId);
            $attribute = $product->getCustomAttribute('no_serialnumber');
            if ($attribute !== null) {
                return (bool)$attribute->getValue();
            }
        } catch (NoSuchEntityException $e) {
        }

        return false;
    }
}