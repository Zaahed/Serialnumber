<?php
declare(strict_types=1);


namespace Zaahed\Serialnumber\Test\Integration\Action;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use Zaahed\Serialnumber\Action\IsSerialnumbersDisabledForOrderItem;
use Zaahed\Serialnumber\Action\SetSourceItemForSerialnumber;
use Zaahed\Serialnumber\Test\Integration\TestCase;

/**
 * @magentoDataFixture Magento/Catalog/_files/product_simple.php
 * @magentoDataFixture Zaahed_Serialnumber::Test/Integration/_files/serialnumber.php
 * @magentoAppArea adminhtml
 */
class SetSourceItemForSerialnumberTest extends TestCase
{
    public function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->productRepository = $this->objectManager
            ->create(ProductRepositoryInterface::class);
        $this->setSourceItemForSerialnumber = $this->objectManager
            ->create(SetSourceItemForSerialnumber::class);
        $this->sourceItemRepository = $this->objectManager
            ->create(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager
            ->create(SearchCriteriaBuilder::class);
    }

    public function testExecute()
    {
        $expectedSerialnumberData = [
            'entity_id' => 1,
            'serialnumber' => 'A7B2D9F1G4',
            'is_available' => true,
            'purchase_price' => 99.99,
        ];
        $sku = 'simple';
        $sourceCode = 'default';

        $this->setSourceItemForSerialnumber->execute(
            1,
            $sku,
            $sourceCode
        );

        $this->searchCriteriaBuilder->addFilter(SourceItemInterface::SKU, $sku);
        $this->searchCriteriaBuilder->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        /** @var SourceItemInterface $sourceItem */
        $sourceItem = reset($sourceItems);
        $serialnumbers = $sourceItem->getExtensionAttributes()->getSerialnumbers();

        $this->assertIsArray($serialnumbers);
        $this->assertCount(1, $serialnumbers);

        $serialnumber = $serialnumbers[0];
        foreach ($expectedSerialnumberData as $key => $value) {
            $this->assertEquals(
                $value,
                $serialnumber->getData($key)
            );
        }
    }
}
