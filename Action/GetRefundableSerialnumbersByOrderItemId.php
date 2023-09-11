<?php

namespace Zaahed\Serialnumber\Action;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\CreditmemoItemRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\CollectionFactory;

class GetRefundableSerialnumbersByOrderItemId
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CreditmemoItemRepositoryInterface
     */
    private $creditmemoItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param CollectionFactory $collectionFactory
     * @param CreditmemoItemRepositoryInterface $creditmemoItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        CreditmemoItemRepositoryInterface $creditmemoItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->creditmemoItemRepository = $creditmemoItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get refundable serial numbers.
     *
     * @param int $orderItemId
     * @return array
     */
    public function execute(int $orderItemId)
    {
        $result = [];

        $collection = $this->collectionFactory->create();
        $collection->addFilter('item_id', $orderItemId);
        $collection->join(
            ['serialnumber_table' => 'serialnumber'],
            'main_table.serialnumber_id = serialnumber_table.entity_id',
            'serialnumber'
        );

        $creditmemoItemIds = $this->getCreditmemoItemIds($orderItemId);
        if (!empty($creditmemoItemIds)) {
            $collection->addFieldToFilter(
                'serialnumber_id',
                [
                    'nin' => new \Zend_Db_Expr(sprintf(
                        '(SELECT serialnumber_id FROM sales_creditmemo_item_serialnumber WHERE item_id IN (%s))',
                        implode(',', $creditmemoItemIds)
                    ))
                ]
            );
        }

        foreach ($collection->getItems() as $item) {
            /** @var Serialnumber $item */
            $result[$item->getSerialnumberId()] = $item->getData('serialnumber');
        }

        return $result;
    }

    /**
     * Get credit memo item ids by order item id.
     *
     * @param int|string $orderItemId
     * @return array
     */
    private function getCreditmemoItemIds($orderItemId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(CreditmemoItemInterface::ORDER_ITEM_ID, $orderItemId)
            ->create();

        return array_map(function($item) {
            return $item->getEntityId();
        }, $this->creditmemoItemRepository->getList($criteria)->getItems());
    }
}