<?php

namespace Zaahed\Serialnumber\Plugin\InventoryApi\Api\SourceItemsSaveInterface;

use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory as SourceItemCollectionFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber;
use Zaahed\Serialnumber\Model\Serialnumber\LogManager;

class SaveSerialnumbers
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
     * @var LogManager
     */
    private $logManager;

    /**
     * @var array
     */
    private $sourceItemCache;

    /**
     * @var SourceItemCollectionFactory
     */
    private $sourceItemCollectionFactory;

    /**
     * @param SaveMultiple $saveMultiple
     * @param CollectionFactory $collectionFactory
     * @param LogManager $logManager
     * @param SourceItemCollectionFactory $sourceItemCollectionFactory
     */
    public function __construct(
        SaveMultiple $saveMultiple,
        CollectionFactory $collectionFactory,
        LogManager $logManager,
        SourceItemCollectionFactory $sourceItemCollectionFactory
    ) {
        $this->saveMultiple = $saveMultiple;
        $this->collectionFactory = $collectionFactory;
        $this->logManager = $logManager;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
    }

    /**
     * Save serial numbers after saving source items.
     *
     * @param SourceItemsSaveInterface $subject
     * @param $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    public function afterExecute(
        SourceItemsSaveInterface $subject,
        $result,
        array $sourceItems
    ): void {
        $this->loadIdsForSourceItems($sourceItems);
        $serialnumbersToRemove = $this->getSerialnumbers($sourceItems);
        $serialnumbersToSave = [];

        foreach ($sourceItems as $item) {
            $serialnumbers = $item->getExtensionAttributes()->getSerialnumbers() ?? [];
            foreach ($serialnumbers as $serialnumber) {
                $serialnumberValue = $serialnumber->getSerialnumber();
                if (isset($serialnumbersToRemove[$serialnumberValue])) {
                    /** @var Serialnumber $serialnumber */
                    $serialnumber = $serialnumbersToRemove[$serialnumberValue];
                    $oldSourceItemId = $serialnumber->getSourceItemId();
                    unset($serialnumbersToRemove[$serialnumberValue]);
                }
                $this->addLogMessageForSavedSerialnumber(
                    $serialnumber,
                    $item,
                    isset($oldSourceItemId) ? $this->getSourceItem($oldSourceItemId) : null
                );
                $serialnumber->setSourceItemId($item->getId());
                $serialnumbersToSave[] = $serialnumber;
                unset($oldSourceItemId);
            }
        }

        $this->addLogMessageForRemovedSerialnumbers($serialnumbersToRemove);
        foreach ($serialnumbersToRemove as $serialnumber) {
            $serialnumber->setSourceItemId(null);
            $serialnumbersToSave[] = $serialnumber;
        }

        $this->saveMultiple->execute($serialnumbersToSave);
        $this->logManager->save();
    }

    /**
     * @param Serialnumber $serialnumber
     * @param SourceItemInterface $sourceItem
     * @param SourceItemInterface|null $oldSourceItem
     * @return void
     */
    private function addLogMessageForSavedSerialnumber(
        $serialnumber,
        $sourceItem,
        $oldSourceItem = null
    ) {
        if ($oldSourceItem !== null && (int)$oldSourceItem->getId() !== (int)$sourceItem->getId()) {
            if ($sourceItem->getSku() === $oldSourceItem->getSku()) {
                $this->logManager->addLogBySerialnumberId(
                    (int)$serialnumber->getId(),
                    'Transferred serial number from source %1 to source %2.',
                    [
                        $oldSourceItem->getSourceCode(),
                        $sourceItem->getSourceCode()
                    ]
                );
            } else {
                $this->logManager->addLogBySerialnumberId(
                    (int)$serialnumber->getId(),
                    'Transferred serial number from SKU %1 and source %2 to SKU %3 and source %4.',
                    [
                        $oldSourceItem->getSku(),
                        $oldSourceItem->getSourceCode(),
                        $sourceItem->getSku(),
                        $sourceItem->getSourceCode()
                    ]
                );
            }
        } elseif ($oldSourceItem === null) {
            $this->logManager->addLog(
                $serialnumber->getSerialnumber(),
                'Added serial number to source %1.',
                [$sourceItem->getSourceCode()]
            );
        }
    }

    /**
     * Load source item IDs for newly created source items that have been
     * saved.
     *
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    private function loadIdsForSourceItems(array $sourceItems)
    {
        $sourceItemsBySkuSourceCodeKey = [];
        $collection = $this->sourceItemCollectionFactory->create();

        foreach ($sourceItems as $item) {
            if ($item->getId() !== null) {
                continue;
            }

            $key = $item->getSku() . $item->getSourceCode();
            $sourceItemsBySkuSourceCodeKey[$key] = $item;
            $collection->getSelect()->orWhere("sku = ?", $item->getSku());
            $collection->getSelect()->where("source_code = ?", $item->getSourceCode());
        }

        if (empty($sourceItemsBySkuSourceCodeKey)) {
            return;
        }

        foreach ($collection as $item) {
            $key = $item->getSku() . $item->getSourceCode();
            $sourceItemsBySkuSourceCodeKey[$key]->setId($item->getId());
            $this->sourceItemCache[$item->getId()] = $item;
        }
    }

    /**
     * Get source code by source item ID.
     *
     * @param int|string $sourceItemId
     * @return SourceItemInterface|null
     */
    private function getSourceItem($sourceItemId)
    {
        if (!isset($this->sourceItemCache[$sourceItemId])) {
            $collection = $this->sourceItemCollectionFactory->create();
            $collection->addFilter('source_item_id', $sourceItemId);
            $this->sourceItemCache[$sourceItemId] = $collection->getFirstItem();
        }
        return $this->sourceItemCache[$sourceItemId] ?? null;
    }

    /**
     * Get current and new serial numbers for each source item.
     *
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    private function getSerialnumbers($sourceItems)
    {
        $result = [];
        $sourceItemIds = [];
        $serialnumbers = [];

        foreach ($sourceItems as $item) {
            $sourceItemIds[] = $item->getId();
            if ($item->getExtensionAttributes()->getSerialnumbers() === null) {
                continue;
            }
            foreach ($item->getExtensionAttributes()->getSerialnumbers() as $serialnumber) {
                $serialnumbers[] = $serialnumber->getSerialnumber();
            }
        }
        $sourceItemIds = array_filter($sourceItemIds);

        // Loads current serial numbers that are set for the source item.
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(SerialnumberInterface::SOURCE_ITEM_ID, ['in' => $sourceItemIds]);
        $collection->addFieldToFilter(SerialnumberInterface::IS_AVAILABLE, ['eq' => 1]);
        foreach ($collection as $serialnumber) {
            $result[$serialnumber->getSerialnumber()] = $serialnumber;
        }

        // Loads new serial numbers that have been set for the source item
        // and have an old source item.
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(SerialnumberInterface::SERIALNUMBER, ['in' => $serialnumbers]);
        $collection->addFieldToFilter(
            SerialnumberInterface::SOURCE_ITEM_ID,
            ['is' => new \Zend_Db_Expr('not null')]
        );
        foreach ($collection as $serialnumber) {
            $result[$serialnumber->getSerialnumber()] = $serialnumber;
        }

        return $result;
    }

    /**
     * Add log message for each serial number that is removed from a source.
     *
     * @param array $serialnumbers
     * @return void
     */
    private function addLogMessageForRemovedSerialnumbers(array $serialnumbers)
    {
        foreach ($serialnumbers as $serialnumber) {
            $sourceItemId = $serialnumber->getSourceItemId();
            $sourceCode = $this->getSourceItem($sourceItemId)->getSourceCode();
            $this->logManager->addLogBySerialnumberId(
                (int)$serialnumber->getId(),
                'Removed serial number from source %1.',
                [$sourceCode]
            );
        }
    }
}
