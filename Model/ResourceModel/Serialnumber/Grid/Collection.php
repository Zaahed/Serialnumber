<?php

namespace Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Grid;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param string $mainTable
     * @param null|string $resourceModel
     * @param null|string $identifierName
     * @param null|string $connectionName
     * @throws LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        parent::__construct($entityFactory, $logger, $fetchStrategy,
            $eventManager, $mainTable, $resourceModel, $identifierName,
            $connectionName);
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $attribute = $this->productAttributeRepository->get('name');
        $attributeId = $attribute->getAttributeId();

        $this->getSelect()
            ->joinLeft(
            'inventory_source_item',
            'main_table.source_item_id = inventory_source_item.source_item_id',
            ['sku', 'source_code']
            )
            ->joinleft(
                'catalog_product_entity AS product',
                'inventory_source_item.sku = product.sku',
                'entity_id AS product_id'
            )
            ->joinLeft(
                'catalog_product_entity_varchar AS product_varchar',
                sprintf(
                    'product.entity_id = product_varchar.entity_id AND attribute_id = %s and store_id = %s',
                    $attributeId, Store::DEFAULT_STORE_ID
                ),
                'value AS product_name'
            )
            ->joinLeft(
                'sales_order_item_serialnumber',
                'main_table.entity_id = sales_order_item_serialnumber.serialnumber_id',
                'item_id'
            )
            ->joinLeft(
                'sales_order_item',
                'sales_order_item_serialnumber.item_id = sales_order_item.item_id',
                'base_price_incl_tax AS selling_price'
            )
            ->group('main_table.entity_id');

        $this->addFilterToMap('sku', 'inventory_source_item.sku');
        $this->addFilterToMap('source_code', 'inventory_source_item.source_code');
        $this->addFilterToMap('product_name', 'product_varchar.value');
        $this->addFilterToMap('selling_price', 'sales_order_item.base_price_incl_tax');
        $this->addFilterToMap('entity_id', 'main_table.entity_id');
    }
}