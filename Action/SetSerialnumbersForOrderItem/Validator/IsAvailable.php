<?php

namespace Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem\Validator;

use Magento\Framework\Validation\ValidationResult;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;

class IsAvailable implements ValidatorInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function validate(int $itemId, array $serialnumbers): array
    {
        $collection = $this->collectionFactory->create();
        $collection->join(
            'sales_order_item_serialnumber',
            'main_table.entity_id = sales_order_item_serialnumber.serialnumber_id',
            'item_id'
        )->getSelect()->group('main_table.entity_id');
        $collection->addFieldToFilter('serialnumber', ['in' => $serialnumbers]);
        $collection->addFieldToFilter('is_available', ['eq' => 0]);
        $collection->addFieldToFilter('item_id', ['neq' => $itemId]);
        $collection->addFieldToFilter('item_id', ['is' => new \Zend_Db_Expr('not null')]);

        if ($collection->count() === 0) {
            return [];
        }

        return [__('One or more serial numbers are not available.')];
    }
}