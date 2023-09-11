<?php
declare(strict_types=1);


namespace Zaahed\Serialnumber\Test\Integration\Action;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use Zaahed\Serialnumber\Action\IsSerialnumbersDisabledForOrderItem;
use Zaahed\Serialnumber\Test\Integration\TestCase;

/**
 * @magentoDataFixture Magento/Sales/_files/order.php
 */
class IsSerialnumbersDisabledForOrderItemTest extends TestCase
{
    public function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->productRepository = $this->objectManager
            ->create(ProductRepositoryInterface::class);
        $this->isSerialnumbersDisabledForOrderItem = $this->objectManager->create(
                IsSerialnumbersDisabledForOrderItem::class,
                ['productRepository' => $this->productRepository]
            );

    }

    /***
     * @return void
     */
    public function testExecuteTrue()
    {
        $this->testExecute(true);
    }

    /**
     * @return void
     */
    public function testExecuteFalse()
    {
        $this->testExecute(false);
    }

    private function testExecute($expected)
    {
        $product = $this->productRepository->get('simple');
        $this->setSerialsnumbersDisabledAttribute($product, $expected);

        $result = $this->isSerialnumbersDisabledForOrderItem->execute($this->getOrderItemId());
        $this->assertEquals(
            $expected,
            $result
        );
    }

    private function setSerialsnumbersDisabledAttribute($product, $status)
    {
        $product->setData('no_serialnumber', (string)$status);
        $this->productRepository->save($product);
    }

}
