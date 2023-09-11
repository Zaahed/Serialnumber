<?php

namespace Zaahed\Serialnumber\Plugin\Sales\Model\ResourceModel\Order\Creditmemo\Item;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResource;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item as ItemResource;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber;
use Zaahed\Serialnumber\Model\Serialnumber\LogManager;

class ProcessReturnToStock
{
    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;
    /**
     * @var SourceItemResource
     */
    private $sourceItemResource;

    /**
     * @var LogManager
     */
    private $logManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param SaveMultiple $saveMultiple
     * @param CollectionFactory $collectionFactory
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemResource $sourceItemResource
     * @param LogManager $logManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        SaveMultiple $saveMultiple,
        CollectionFactory $collectionFactory,
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemResource $sourceItemResource,
        LogManager $logManager,
        ProductRepositoryInterface $productRepository
    ) {
        $this->saveMultiple = $saveMultiple;
        $this->collectionFactory = $collectionFactory;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemResource = $sourceItemResource;
        $this->logManager = $logManager;
        $this->productRepository = $productRepository;
    }

    /**
     * Process return to stock.
     *
     * @param ItemResource $subject
     * @param ItemResource $result
     * @param AbstractModel $item
     * @return ItemResource
     */
    public function afterSave(ItemResource $subject, ItemResource $result, AbstractModel $item)
    {
        /** @var CreditmemoItemInterface $item */
        $serialnumbers = $item->getExtensionAttributes()->getSerialnumbers();
        $returnSource = $item->getExtensionAttributes()->getReturnToSource();
        if (!$item->getBackToStock() ||
            $serialnumbers === null) {
            return $result;
        }
        if ($returnSource !== null) {
            $sku = $this->getRealSku($item->getProductId(), $item->getSku());
            $sourceItemId = $this->getSourceItemId($sku, $returnSource);
        }

        $serialnumberIds = array_map(function($serialnumber) {
            return $serialnumber->getSerialnumberId();
        }, $serialnumbers);

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['in' => $serialnumberIds]);
        /** @var Serialnumber[] $serialnumbersToSave */
        $serialnumbersToSave = $collection->getItems();
        foreach ($serialnumbersToSave as $serialnumber) {
            $serialnumber->setIsAvailable(true);
            $this->logManager->addLogBySerialnumberId(
                $serialnumber->getId(),
                'Set status to available.'
            );
            if (isset($sourceItemId)) {
                $serialnumber->setSourceItemId($sourceItemId);
                $this->logManager->addLogBySerialnumberId(
                    $serialnumber->getId(),
                    'Returned serial number to source %1.',
                    [$returnSource]
                );
            }
        }

        $this->saveMultiple->execute($serialnumbersToSave);
        $this->logManager->save();

        return $result;
    }

    /**
     * Get source item ID by SKU and source.
     *
     * @param string $sku
     * @param string $sourceCode
     * @return int
     */
    private function getSourceItemId($sku, $sourceCode)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();

        $sourceItem = current(
            $this->sourceItemRepository->getList($criteria)->getItems()
        );
        if ($sourceItem === false) {
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku($sku);
            $sourceItem->setSourceCode($sourceCode);
            $sourceItem->setQuantity(1);
            $sourceItem->setStatus(1);
            $this->sourceItemResource->save($sourceItem);
        }

        return $sourceItem->getId();
    }

    /**
     * Get real SKU by product ID or return the current SKU
     * if the product is not available. Custom options can
     * modify the SKU.
     *
     * @param int|null $productId
     * @param string $sku
     * @return string|null
     */
    private function getRealSku($productId, $sku) {
        if ($productId === null) {
            return $sku;
        }

        try {
            $product = $this->productRepository->getById($productId);
            return $product->getSku();
        } catch (NoSuchEntityException $e) {
            return $sku;
        }
    }
}
