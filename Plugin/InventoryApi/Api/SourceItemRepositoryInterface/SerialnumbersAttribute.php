<?php

namespace Zaahed\Serialnumber\Plugin\InventoryApi\Api\SourceItemRepositoryInterface;

use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;

class SerialnumbersAttribute
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function afterGetList(
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemSearchResultsInterface $result
    ): SourceItemSearchResultsInterface {
        $sourceItemIds = [];

        foreach ($result->getItems() as $item) {
            $sourceItemIds[] = $item->getId();
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('source_item_id', ['in' => $sourceItemIds]);

        foreach ($result->getItems() as $item) {
            $serialnumbers = $collection
                ->getItemsByColumnValue('source_item_id', $item->getId());
            $item->getExtensionAttributes()->setSerialnumbers($serialnumbers);
        }

        return $result;
    }
}