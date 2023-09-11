<?php

namespace Zaahed\Serialnumber\Action;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\ShipmentItemRepositoryInterface;
use Zaahed\Serialnumber\Model\Order\Item\Serialnumber;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\CollectionFactory;

class GetShippableSerialnumbersByOrderItemId
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ShipmentItemRepositoryInterface
     */
    private $shipmentItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ShipmentItemRepositoryInterface $shipmentItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ShipmentItemRepositoryInterface $shipmentItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->shipmentItemRepository = $shipmentItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get shippable serial numbers with serial number ID as key.
     *
     * @param int $orderItemId
     * @return array
     */
    public function execute(int $orderItemId): array
    {
        $result = [];

        $collection = $this->collectionFactory->create();
        $collection->addFilter('item_id', $orderItemId);
        $collection->join(
            ['serialnumber_table' => 'serialnumber'],
            'main_table.serialnumber_id = serialnumber_table.entity_id',
            'serialnumber'
        );

        $shipmentItemIds = $this->getShipmentItemIds($orderItemId);
        if (!empty($shipmentItemIds)) {
            $collection->addFieldToFilter(
                'serialnumber_id',
                [
                    'nin' => new \Zend_Db_Expr(sprintf(
                        '(SELECT serialnumber_id FROM sales_shipment_item_serialnumber WHERE item_id IN (%s))',
                        implode(',', $shipmentItemIds)
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
     * Get shipment item ids by order item id.
     *
     * @param int|string $orderItemId
     * @return array
     */
    private function getShipmentItemIds($orderItemId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(ShipmentItemInterface::ORDER_ITEM_ID, $orderItemId)
            ->create();

        return array_map(function ($item) {
            return $item->getEntityId();
        }, $this->shipmentItemRepository->getList($criteria)->getItems());
    }
}
