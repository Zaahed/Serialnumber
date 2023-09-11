<?php

namespace Zaahed\Serialnumber\Ui\DataProvider\Serialnumber\Form\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

class ProductInfo implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterfaceFactory $productFactory
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository,
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritDoc
     */
    public function modifyData(array $data)
    {
        if (!isset($data['items'][0])) {
            return $data;
        }

        $item = $data['items'][0];
        if ($item['source_item_id'] === null) {
            return $data;
        }
        $product = $this->getProductBySourceItemId(
            $item['source_item_id']
        );

        $item['sku'] = $product->getSku();
        $item['product_name'] = $product->getName();
        $item['sell_price'] = $product->getPrice();

        $data['items'][0] = $item;

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Get product by source item ID.
     *
     * @return ProductInterface
     */
    private function getProductBySourceItemId($sourceItemId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('source_item_id', $sourceItemId)
            ->create();

        /** @var SourceItemInterface[] $sourceItems */
        $sourceItems = $this->sourceItemRepository
            ->getList($criteria)
            ->getItems();

        if (empty($sourceItems)) {
            return $this->productFactory->create();
        }

        $sku = current($sourceItems)->getSku();
        try {
            return $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            return $this->productFactory->create();
        }

    }
}