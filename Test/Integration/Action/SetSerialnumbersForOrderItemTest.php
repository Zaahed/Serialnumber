<?php
declare(strict_types=1);


namespace Zaahed\Serialnumber\Test\Integration\Action;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\Serialnumber\LogManager;

/**
 * @magentoDataFixture Magento/Sales/_files/order.php
 */
class SetSerialnumbersForOrderItemTest extends TestCase
{
    public function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->setSerialnumbersForOrderItem = $this->objectManager
            ->create(
                SetSerialnumbersForOrderItem::class,
                ['logManager' => $this->createMock(LogManager::class)]
            );
    }

    /**
     * Test adding serial numbers for order item.
     *
     * @return void
     */
    public function testAddSerialnumbersForOrderItem()
    {
        $serialnumbers = [
            'H5Y3M8N2',
            'X7B9K2R3'
        ];
        $itemId = $this->getOrderItemId();

        $this->setSerialnumbersForOrderItem->execute($itemId, $serialnumbers);
        $this->assertEquals(
            $this->getSerialnumbersByItemId($itemId),
            $serialnumbers
        );
    }

    /**
     * Test updating serial numbers for order item.
     *
     * @return void
     */
    public function testUpdateSerialnumbersForOrderItem()
    {
        $serialnumbers = [
            'H5Y3M8N2',
            'X7B9K2R3'
        ];
        $itemId = $this->getOrderItemId();

        $this->setSerialnumbersForOrderItem->execute($itemId, ["SN745A9X"]);
        $this->setSerialnumbersForOrderItem->execute($itemId, $serialnumbers);
        $this->assertEquals(
            $this->getSerialnumbersByItemId($itemId),
            $serialnumbers
        );
    }

    /**
     * Get serial numbers associated with order item ID.
     *
     * @param int $itemId
     * @return array
     */
    private function getSerialnumbersByItemId($itemId)
    {
        $collection = $this->objectManager->get(CollectionFactory::class)->create();
        $collection->addFieldToFilter('item_id', ['eq' => $itemId]);
        $collection->join(
            'serialnumber',
            'main_table.serialnumber_id = serialnumber.entity_id',
            SerialnumberInterface::SERIALNUMBER
        );

        return $collection->getColumnValues(SerialnumberInterface::SERIALNUMBER);
    }

    /**
     * Get order item ID for testing.
     *
     * @return int
     */
    private function getOrderItemId()
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
