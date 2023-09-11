<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Zaahed\Serialnumber\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber as OrderItemSerialnumberResource;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\DeleteMultiple;
use Zaahed\Serialnumber\Model\Serialnumber;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class RemoveIncompleteSerialnumbers implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var DeleteMultiple
     */
    private $deleteMultiple;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CollectionFactory $collectionFactory
     * @param DeleteMultiple $deleteMultiple
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CollectionFactory $collectionFactory,
        DeleteMultiple $deleteMultiple
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->collectionFactory = $collectionFactory;
        $this->deleteMultiple = $deleteMultiple;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $orderItemTable = OrderItemSerialnumberResource::TABLE_NAME;

        $collection = $this->collectionFactory->create();
        $collection->getSelect()->joinLeft(
            $orderItemTable,
            sprintf(
            'main_table.entity_id = %s.serialnumber_id',
            $orderItemTable),
            'item_id'
        )->group('main_table.entity_id');
        $collection->addFieldToFilter(SerialnumberInterface::SOURCE_ITEM_ID,
            ['is' => new \Zend_Db_Expr('null')]);
        $collection->addFieldToFilter(SerialnumberInterface::PURCHASE_PRICE,
            ['is' => new \Zend_Db_Expr('null')]);
        $collection->addFieldToFilter('item_id',
            ['is' => new \Zend_Db_Expr('null')]);

        /** @var Serialnumber[] $serialnumbersToRemove */
        $serialnumbersToRemove = array_values($collection->getItems());
        $this->deleteMultiple->execute($serialnumbersToRemove);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
