<?php

namespace Zaahed\Serialnumber\Action;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem\Validator\ValidatorInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\Order\Item\Serialnumber as OrderItemSerialnumber;
use Zaahed\Serialnumber\Model\Order\Item\SerialnumberFactory as OrderItemSerialnumberFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber as OrderItemSerialnumberResource;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\CollectionFactory as OrderItemSerialnumberCollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\DeleteMultiple as DeleteMultipleOrderItemSerialnumbers;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\SaveMultiple as SaveMultipleOrderItemSerialnumbers;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as SerialnumberResource;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory as SerialnumberCollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\DeleteMultiple as DeleteMultipleSerialnumbers;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple as SaveMultipleSerialnumbers;
use Zaahed\Serialnumber\Model\Serialnumber;
use Zaahed\Serialnumber\Model\Serialnumber\LogManager;
use Zaahed\Serialnumber\Model\SerialnumberFactory;

class SetSerialnumbersForOrderItem
{
    /**
     * @var SerialnumberCollectionFactory
     */
    private $serialnumberCollectionFactory;

    /**
     * @var SaveMultipleSerialnumbers
     */
    private $saveMultipleSerialnumbers;

    /**
     * @var DeleteMultipleSerialnumbers
     */
    private $deleteMultipleSerialnumbers;

    /**
     * @var SerialnumberFactory
     */
    private $serialnumberFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var OrderItemSerialnumberCollectionFactory
     */
    private $orderItemSerialnumberCollectionFactory;
    /**
     * @var OrderItemSerialnumberFactory
     */
    private $orderItemSerialnumberFactory;

    /**
     * @var SaveMultipleOrderItemSerialnumbers
     */
    private $saveMultipleOrderItemSerialnumbers;

    /**
     * @var DeleteMultipleOrderItemSerialnumbers
     */
    private $deleteMultipleOrderItemSerialnumbers;

    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LogManager
     */
    private $logManager;

    /**
     * @var int|null
     */
    private $itemId = null;

    /**
     * @var array|null
     */
    private $serialnumbers = null;

    /**
     * @param SerialnumberCollectionFactory $serialnumberCollectionFactory
     * @param OrderItemSerialnumberCollectionFactory $orderItemSerialnumberCollectionFactory
     * @param SaveMultipleSerialnumbers $saveMultipleSerialnumbers
     * @param DeleteMultipleSerialnumbers $deleteMultipleSerialnumbers
     * @param SaveMultipleOrderItemSerialnumbers $saveMultipleOrderItemSerialnumbers
     * @param DeleteMultipleOrderItemSerialnumbers $deleteMultipleOrderItemSerialnumbers
     * @param SerialnumberFactory $serialnumberFactory
     * @param OrderItemSerialnumberFactory $orderItemSerialnumberFactory
     * @param ResourceConnection $resourceConnection
     * @param ValidationResultFactory $validationResultFactory
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param UrlInterface $urlBuilder
     * @param LogManager $logManager
     * @param array $validators
     */
    public function __construct(
        SerialnumberCollectionFactory $serialnumberCollectionFactory,
        OrderItemSerialnumberCollectionFactory $orderItemSerialnumberCollectionFactory,
        SaveMultipleSerialnumbers $saveMultipleSerialnumbers,
        DeleteMultipleSerialnumbers $deleteMultipleSerialnumbers,
        SaveMultipleOrderItemSerialnumbers $saveMultipleOrderItemSerialnumbers,
        DeleteMultipleOrderItemSerialnumbers $deleteMultipleOrderItemSerialnumbers,
        SerialnumberFactory $serialnumberFactory,
        OrderItemSerialnumberFactory $orderItemSerialnumberFactory,
        ResourceConnection $resourceConnection,
        ValidationResultFactory $validationResultFactory,
        OrderItemRepositoryInterface $orderItemRepository,
        UrlInterface $urlBuilder,
        LogManager $logManager,
        array $validators = []
    ) {
        $this->serialnumberCollectionFactory = $serialnumberCollectionFactory;
        $this->saveMultipleSerialnumbers = $saveMultipleSerialnumbers;
        $this->serialnumberFactory = $serialnumberFactory;
        $this->resourceConnection = $resourceConnection;
        $this->orderItemSerialnumberCollectionFactory
            = $orderItemSerialnumberCollectionFactory;
        $this->orderItemSerialnumberFactory = $orderItemSerialnumberFactory;
        $this->saveMultipleOrderItemSerialnumbers
            = $saveMultipleOrderItemSerialnumbers;
        $this->deleteMultipleOrderItemSerialnumbers
            = $deleteMultipleOrderItemSerialnumbers;
        $this->validationResultFactory = $validationResultFactory;
        $this->validators = $validators;
        $this->orderItemRepository = $orderItemRepository;
        $this->urlBuilder = $urlBuilder;
        $this->deleteMultipleSerialnumbers = $deleteMultipleSerialnumbers;
        $this->logManager = $logManager;
    }

    /**
     * Set serial numbers for order item.
     *
     * @param int $itemId
     * @param array $serialnumbers
     * @return void
     * @throws \Exception
     */
    public function execute(int $itemId, array $serialnumbers)
    {
        $this->itemId = $itemId;
        $this->serialnumbers = array_map('strtoupper', $serialnumbers);

        $this->validate();

        try {
            $this->resourceConnection->getConnection()->beginTransaction();

            $this->createSerialnumberIfNotExists();

            $this->deleteOrderItemSerialnumbers();
            $this->saveOrderItemSerialnumbers();

            $this->setAvailability($this->serialnumbers, false);

            $this->logManager->save();

            $this->resourceConnection->getConnection()->commit();
        } catch (\Exception $e) {
            $this->resourceConnection->getConnection()->rollBack();
            throw $e;
        }

        $this->itemId = null;
        $this->serialnumbers = null;
    }

    /**
     * Validate.
     *
     * @return void
     * @throws ValidationException
     */
    private function validate()
    {
        $errors = [];

        foreach ($this->validators as $validator) {
            $errors[] = $validator->validate($this->itemId, $this->serialnumbers);
        }

        $errors = array_merge(...$errors);

        $validationResult = $this->validationResultFactory->create(['errors' => $errors]);
        if (!$validationResult->isValid()) {
            throw new ValidationException(
                __('Validation failed: %1', current($errors)),
                null,
                0,
                $validationResult
            );
        }
    }

    /**
     * Delete serial numbers that are not linked to any product or any other
     * order item.
     *
     * @param array $serialnumberIds
     * @return void
     */
    private function deleteSerialnumbers(array $serialnumberIds)
    {
        $serialnumbersToDelete = [];

        $collection = $this->serialnumberCollectionFactory->create();
        $collection->getSelect()
            ->joinLeft(
                OrderItemSerialnumberResource::TABLE_NAME,
                sprintf(
                    'main_table.entity_id = %s.serialnumber_id and %s.item_id != %s',
                    OrderItemSerialnumberResource::TABLE_NAME,
                    OrderItemSerialnumberResource::TABLE_NAME,
                    $this->itemId
                ),
                ['item_id']
            )
            ->group('main_table.entity_id');
        $collection
            ->addFieldToFilter('entity_id', ['in' => $serialnumberIds]);

        /** @var Serialnumber $item */
        foreach ($collection as $item) {
            if ($item->getSourceItemId() !== null ||
                $item->getItemId() !== null) {
                continue;
            }
            $serialnumbersToDelete[] = $item;
        }

        $this->deleteMultipleSerialnumbers->execute($serialnumbersToDelete);
    }

    /**
     * Delete order item serial numbers that are not provided.
     *
     * @return void
     */
    private function deleteOrderItemSerialnumbers()
    {
        /** @var OrderItemSerialnumber[] $serialnumberItems */
        $collection = $this->orderItemSerialnumberCollectionFactory->create();
        $collection->join(
            SerialnumberResource::TABLE_NAME,
            sprintf(
                'main_table.serialnumber_id = %s.entity_id',
                SerialnumberResource::TABLE_NAME
            ),
            'serialnumber'
        );
        $collection->addFieldToFilter('item_id', ['eq' => $this->itemId]);

        if (!empty($this->serialnumbers)) {
            $collection->addFieldToFilter(
                SerialnumberInterface::SERIALNUMBER,
                ['nin' => $this->serialnumbers]
            );
        }

        $serialnumberIds = array_map(function ($item) {
            /** @var OrderItemSerialnumber $item */
            return $item->getSerialnumberId();
        }, $collection->getItems());
        $this->deleteSerialnumbers($serialnumberIds);

        /** @var OrderItemSerialnumber[] $serialnumberItems */
        $serialnumberItems = $collection->clear()->getItems();

        if (empty($serialnumberItems)) {
            return;
        }

        $this->deleteMultipleOrderItemSerialnumbers->execute($serialnumberItems);
        $serialnumberIdValueMap = $this->getAllSerialnumbers();
        $deletedSerialnumbers = array_map(function ($item) use ($serialnumberIdValueMap) {
            return $serialnumberIdValueMap[$item->getSerialnumberId()];
        }, $serialnumberItems);

        $this->setAvailability($deletedSerialnumbers, true);

        $this->createLogEntry(
            $deletedSerialnumbers,
            'Removed serial number from order item %1.',
            [$this->getOrderHtmlUrl()]
        );
    }

    /**
     * Save order item serial numbers.
     *
     * @return void
     */
    private function saveOrderItemSerialnumbers()
    {
        $serialnumberValueIdMap = array_flip($this->getAllSerialnumbers());
        $serialnumbersToSave = [];
        $newSerialnumbers = [];

        foreach ($this->serialnumbers as $serialnumber) {
            $serialnumberId = $serialnumberValueIdMap[$serialnumber];
            $item = $this->orderItemSerialnumberFactory->create();
            $item->setItemId($this->itemId);
            $item->setSerialnumberId($serialnumberId);

            $serialnumbersToSave[$serialnumberId] = $item;
            $newSerialnumbers[$serialnumberId] = $serialnumber;
        }

        $collection = $this->orderItemSerialnumberCollectionFactory->create();
        $collection->addFieldToFilter(
            'serialnumber_id',
            ['in' => array_keys($serialnumbersToSave)]
        );
        $collection->addFieldToFilter('item_id', ['eq' => $this->itemId]);

        $alreadySavedSerialnumberIds = $collection->getColumnValues('serialnumber_id');
        foreach ($serialnumbersToSave as $serialnumberId => $item) {
            if (in_array($serialnumberId, $alreadySavedSerialnumberIds)) {
                unset($serialnumbersToSave[$serialnumberId]);
                unset($newSerialnumbers[$serialnumberId]);
            }
        }
        $this->saveMultipleOrderItemSerialnumbers
            ->execute(array_values($serialnumbersToSave));

        $this->createLogEntry(
            array_values($newSerialnumbers),
            'Added serial number to order item %1.',
            [$this->getOrderHtmlUrl()]
        );
    }

    /**
     * Create serial number if it does not exist.
     *
     * @return void
     */
    private function createSerialnumberIfNotExists()
    {
        $currentSerialnumbers = array_values($this->getAllSerialnumbers());
        $serialnumbersToCreate = [];
        $serialnumbersToLog = [];

        foreach ($this->serialnumbers as $serialnumber) {
            if (in_array($serialnumber, $currentSerialnumbers)) {
                continue;
            }

            $item = $this->serialnumberFactory->create();
            $item->setSerialnumber($serialnumber);
            $item->setIsAvailable(true);
            $serialnumbersToCreate[] = $item;
            $serialnumbersToLog[] = $serialnumber;
        }

        $this->saveMultipleSerialnumbers->execute($serialnumbersToCreate);
        $this->createLogEntry(
            $serialnumbersToLog,
            'Serial number created for order item %1.',
            [$this->getOrderHtmlUrl()]
        );
    }

    /**
     * Set availability status for serial numbers.
     *
     * @param array $serialnumbers
     * @param bool $status
     * @return void
     */
    private function setAvailability(array $serialnumbers, bool $status)
    {
        /** @var Serialnumber[] $serialnumberItems */
        $serialnumberItems = $this->serialnumberCollectionFactory
            ->create()
            ->addFieldToFilter(SerialnumberInterface::SERIALNUMBER, ['in' => $serialnumbers])
            ->getItems();
        $serialnumbersToLog = [];

        foreach ($serialnumberItems as $item) {
            if ($item->isAvailable() === $status) {
                continue;
            }

            $item->setIsAvailable($status);
            $serialnumbersToLog[] = $item->getSerialnumber();
        }

        $this->saveMultipleSerialnumbers->execute($serialnumberItems);

        $logStatus = $status ? 'available' : 'unavailable';
        $this->createLogEntry(
            $serialnumbersToLog,
            'Set status to %1.',
            [$logStatus]
        );
    }

    /**
     * Get all serial numbers with ID as key.
     *
     * @return array
     */
    private function getAllSerialnumbers()
    {
        $result = [];

        /** @var Serialnumber[] $serialnumberItems */
        $serialnumberItems = $this->serialnumberCollectionFactory
            ->create()
            ->getItems();

        foreach ($serialnumberItems as $item) {
            $result[$item->getId()] = $item->getSerialnumber();
        }

        return $result;
    }

    /**
     * Create a log entry for each serialnumber value.
     *
     * @param array $serialnumbers
     * @param string $message
     * @param array $values
     * @return void
     */
    private function createLogEntry($serialnumbers, $message, $values = [])
    {
        $serialnumberValueIdMap = array_flip($this->getAllSerialnumbers());
        $serialnumberIdsToLog = [];

        foreach ($serialnumbers as $serialnumber) {
            if (!isset($serialnumberValueIdMap[$serialnumber])) {
                continue;
            }
            $serialnumberIdsToLog[] = $serialnumberValueIdMap[$serialnumber];
        }

        foreach ($serialnumberIdsToLog as $serialnumberId) {
            $this->logManager->addLogBySerialnumberId(
                $serialnumberId,
                $message,
                $values
            );
        }
    }

    /**
     * Get an 'a' tag with the href attribute set to the admin order url.
     *
     * @return string
     */
    private function getOrderHtmlUrl()
    {
        $orderItem = $this->orderItemRepository->get($this->itemId);
        $url = $this->urlBuilder->getUrl(
            'sales/order/view',
            ['order_id' => $orderItem->getOrderId()]
        );

        return sprintf('<a href="%s">%s</a>', $url, $orderItem->getName());
    }
}
