<?php

namespace Zaahed\Serialnumber\Ui\DataProvider\Serialnumber\Form\Modifier;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class SourceInfo implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
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
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceRepository = $sourceRepository;
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

        $sourceItem = $this->getSourceItem($item['source_item_id']);
        if ($sourceItem === null) {
            return $data;
        }
        $source = $this->sourceRepository->get($sourceItem->getSourceCode());

        $item['source_code'] = $sourceItem->getSourceCode();
        $item['source_name'] = $source->getName();
        $item['source_description'] = $source->getDescription();

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
     * Get source item.
     *
     * @return SourceItemInterface|null
     */
    private function getSourceItem($sourceItemId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('source_item_id', $sourceItemId)
            ->create();

        $sourceItems = $this->sourceItemRepository
            ->getList($criteria)
            ->getItems();

        if (empty($sourceItems)) {
            return null;
        }

        return current($sourceItems);
    }
}