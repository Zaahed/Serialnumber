<?php

namespace Zaahed\Serialnumber\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\Serialnumber;

class Serialnumbers implements ModifierInterface
{

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemInterface[]
     */
    private $sourceItems;

    /**
     * @param LocatorInterface $locator
     * @param CollectionFactory $collectionFactory
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     */
    public function __construct(
        LocatorInterface $locator,
        CollectionFactory $collectionFactory,
        GetSourceItemsBySkuInterface $getSourceItemsBySku
    ) {
        $this->locator = $locator;
        $this->collectionFactory = $collectionFactory;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
    }

    /**
     * @inheritDoc
     * @todo Use array_map
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();

        $assignedSources = $data[$product->getId()]['sources']['assigned_sources'] ?? [];

        foreach ($assignedSources as $index => $assignedSource) {
            $sourceItemId = $this->getSourceItemId($product->getSku(),
                $assignedSource['source_code'],
                $index === 0);

            $assignedSource['serialnumbers'] = $this->getSerialnumbers($sourceItemId);
            $assignedSources[$index] = $assignedSource;
        }

        $data[$product->getId()]['sources']['assigned_sources'] = $assignedSources;

        return $data;
    }

    /**
     * Get serial numbers as a string separated by newline.
     *
     * @param $sourceItemId
     * @return string
     * @todo Improve performance by allowing multiple source item ids.
     */
    private function getSerialnumbers($sourceItemId): string
    {
        $serialnumberItems = $this->collectionFactory
            ->create()
            ->addFilter(SerialnumberInterface::SOURCE_ITEM_ID, $sourceItemId)
            ->addFilter(SerialnumberInterface::IS_AVAILABLE, true)
            ->getItems();

        $serialnumbers = [];
        foreach ($serialnumberItems as $item) {
            /** @var Serialnumber $item */
            $serialnumbers[] = $item->getSerialnumber();
        }

        return implode($serialnumbers, PHP_EOL);
    }

    /**
     * Get source item id by sku and source code.
     *
     * @param $sku
     * @param $sourceCode
     * @param bool $reload
     * @return int
     * @throws NoSuchEntityException
     */
    private function getSourceItemId($sku, $sourceCode, bool $reload = false): int
    {
        if ($reload || !isset($this->sourceItems[$sku])) {
            $this->sourceItems[$sku] = $this->getSourceItemsBySku->execute($sku);
        }

        foreach ($this->sourceItems[$sku] as $item) {
            if ($item->getSourceCode() === $sourceCode) {
                return $item->getId();
            }
        }

        throw new NoSuchEntityException(__('Source item not found.'));
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
