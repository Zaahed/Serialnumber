<?php
declare(strict_types=1);


namespace Zaahed\Serialnumber\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;

class TestCase extends \PHPUnit\Framework\TestCase
{

    /**
     * Get order item ID for testing.
     *
     * @return int
     */
    protected function getOrderItemId()
    {
        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);

        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('increment_id', '100000001')
            ->create();

        $orders = $orderRepository->getList($searchCriteria)->getItems();
        $items = reset($orders)->getItems();
        return (int)reset($items)->getItemId();
    }
}
