<?php
declare(strict_types=1);


namespace Zaahed\Serialnumber\Model\Import;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\StringUtils;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterfaceFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\DeleteMultiple;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber\LogManager;

class Serialnumber extends \Magento\ImportExport\Model\Import\AbstractEntity
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SerialnumberInterfaceFactory
     */
    private $serialnumberFactory;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var DeleteMultiple
     */
    private $deleteMultiple;

    /**
     * @var LogManager
     */
    private $logManager;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @inheritDoc
     */
    protected $masterAttributeCode = 'serialnumber';

    /**
     * @var array
     */
    private $sourceCodeCache = [];

    /**
     * @var array
     */
    private $skuCache = [];

    /**
     * @var array
     */
    private $sourceItemCache = [];

    /**
     * @param StringUtils $string
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param Helper $resourceHelper
     * @param ResourceConnection $resource
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param ProductRepositoryInterface $productRepository
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SerialnumberInterfaceFactory $serialnumberFactory
     * @param SaveMultiple $saveMultiple
     * @param DeleteMultiple $deleteMultiple
     * @param LogManager $logManager
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ImportExport\Model\ImportFactory $importFactory,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        ResourceConnection $resource,
        ProcessingErrorAggregatorInterface $errorAggregator,
        ProductRepositoryInterface $productRepository,
        SourceRepositoryInterface $sourceRepository,
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SerialnumberInterfaceFactory $serialnumberFactory,
        SaveMultiple $saveMultiple,
        DeleteMultiple $deleteMultiple,
        LogManager $logManager,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($string,
            $scopeConfig,
            $importFactory,
            $resourceHelper,
            $resource,
            $errorAggregator,
            $data);

        $this->productRepository = $productRepository;
        $this->sourceRepository = $sourceRepository;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serialnumberFactory = $serialnumberFactory;
        $this->saveMultiple = $saveMultiple;
        $this->deleteMultiple = $deleteMultiple;
        $this->logManager = $logManager;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    protected function _importData()
    {
        $serialnumbersToSave = [];
        $serialnumbersToDelete = [];

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $serialnumbers = array_map(function($rowData) {
                return $rowData['serialnumber'];
            }, $bunch);
            $serialnumbersToSave = array_replace(
                $serialnumbersToSave,
                $this->loadSerialnumbers($serialnumbers)
            );

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                $serialnumberData = $this->prepareData($rowData);
                if ($this->getBehavior($rowData) === Import::BEHAVIOR_ADD_UPDATE) {
                    $serialnumber = $serialnumbersToSave[$serialnumberData['serialnumber']] ??
                        $this->serialnumberFactory->create();
                    $serialnumber->addData($serialnumberData);
                    if ($serialnumber->getData(SerialnumberInterface::IS_AVAILABLE) === null) {
                        $serialnumber->setIsAvailable(true);
                    }
                    $serialnumbersToSave[$serialnumber->getSerialnumber()] = $serialnumber;

                    $this->logSerialnumberChanges($serialnumber);
                    $serialnumber->getId() ? $this->countItemsUpdated++ : $this->countItemsCreated++;
                }
                if ($this->getBehavior($rowData) === Import::BEHAVIOR_DELETE) {
                    $serialnumber = $serialnumberData['serialnumber'];
                    if (isset($serialnumbersToSave[$serialnumber])) {
                        $serialnumbersToDelete[] = $serialnumbersToSave[$serialnumber];
                        unset($serialnumbersToSave[$serialnumber]);
                        $this->countItemsDeleted++;
                    }
                }
            }
        }

        $this->deleteMultiple->execute($serialnumbersToDelete);
        $this->saveMultiple->execute($serialnumbersToSave);
        $this->logManager->save();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getEntityTypeCode()
    {
        return 'serialnumber';
    }

    /**
     * @inheritDoc
     */
    public function validateRow(array $rowData, $rowNumber)
    {
        $rowData = $this->trimValues($rowData);
        $this->validateSerialnumber($rowData, $rowNumber);
        $this->validatePurchasePrice($rowData, $rowNumber);
        $this->validateSourceItemId($rowData, $rowNumber);
        $this->validateSourceCodeAndSku($rowData, $rowNumber);

        return !$this->getErrorAggregator()->isRowInvalid($rowNumber);
    }

    /**
     * Validate serial number.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    private function validateSerialnumber(array $rowData, $rowNumber)
    {
        $serialnumber = $rowData['serialnumber'] ?? '';
        if ($serialnumber === '') {
            $this->addRowError(
                'serialnumber',
                $rowNumber,
                'serialnumber',
                __('Missing serial number.')
            );

            $this->getErrorAggregator()->addRowToSkip($rowNumber);
        }
    }

    /**
     * Validate purchase price.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    private function validatePurchasePrice(array $rowData, $rowNumber)
    {
        $purchasePrice = $rowData['purchase_price'] ?? '';
        if ($purchasePrice === '') {
            return;
        }

        if ($purchasePrice !== '0') {
            $purchasePrice = ltrim($rowData['purchase_price'], '0');
        }

        if (!is_numeric($purchasePrice) || $purchasePrice < 0) {
            $this->addRowError(
                'purchase_price',
                $rowNumber,
                'purchase_price',
                __('Invalid purchase price value %1.',
                    [$rowData['purchase_price']])
            );

            $this->getErrorAggregator()->addRowToSkip($rowNumber);
        }
    }

    /**
     * Validate source item ID.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    private function validateSourceItemId(array $rowData, $rowNumber)
    {
        $sourceItemId = $rowData['source_item_id'] ?? '';
        if ($sourceItemId === '') {
            return;
        }
        if (!is_numeric($sourceItemId)) {
            $this->addRowError(
                'source_item_id',
                $rowNumber
                ,'source_item_id',
                __("Invalid source item ID '%1'", [$sourceItemId])
            );

            $this->getErrorAggregator()->addRowToSkip($rowNumber);
            return;
        }

        if (!$this->sourceItemExists($sourceItemId)) {
            $this->addRowError(
                'source_item_id',
                $rowNumber,
                'source_item_id',
                __('No source item found for ID %1.',
                    [$sourceItemId])
            );

            $this->getErrorAggregator()->addRowToSkip($rowNumber);
        }
    }

    /**
     * Validate source code and SKU.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    private function validateSourceCodeAndSku(array $rowData, $rowNumber)
    {
        $sourceItemId = $rowData['source_item_id'] ?? '';
        $sourceCode = $rowData['source_code'] ?? '';
        $sku = $rowData['sku'] ?? '';
        if ($sourceItemId !== '' || ($sourceCode === '' && $sku === '')) {
            return;
        }

        if ($sourceCode === '') {
            $this->addRowError(
                'source_code',
                $rowNumber,
                'source_code',
                __('Source code is missing while SKU is specified.'
                [$rowNumber])
            );

            $this->getErrorAggregator()->addRowToSkip($rowNumber);
            return;
        }

        if ($sku === '') {
            $this->addRowError(
                'sku',
                $rowNumber,
                'sku',
                __('SKU is missing while source code is specified.')
            );

            $this->getErrorAggregator()->addRowToSkip($rowNumber);
            return;
        }

        if (!$this->sourceExists($sourceCode)) {
            $this->addRowError(
                'source_code',
                $rowNumber,
                'source_code',
                __('No source found with source code %1.', [$sourceCode])
            );

            $this->getErrorAggregator()->addRowToSkip($rowNumber);

        }

        if (!$this->productExists($sku)) {
            $this->addRowError(
                'sku',
                $rowNumber,
                'sku',
                __('No product found with SKU %1.', [$sku])
            );

            $this->getErrorAggregator()->addRowToSkip($rowNumber);
        }
    }

    /**
     * Check if source exists using source code.
     *
     * @param string $sourceCode
     * @return bool
     */
    private function sourceExists($sourceCode)
    {
        if (!isset($this->sourceCodeCache[$sourceCode])) {
            try {
                $this->sourceRepository->get($sourceCode);
                $this->sourceCodeCache[$sourceCode] = true;
            } catch (NoSuchEntityException $e) {
                $this->sourceCodeCache[$sourceCode] = false;
            }
        }

        return $this->sourceCodeCache[$sourceCode];
    }

    /**
     * Check if source item exists using source item ID.
     *
     * @param int $sourceItemId
     * @return bool
     */
    private function sourceItemExists($sourceItemId)
    {
        if (!isset($this->sourceItemCache[$sourceItemId])) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('source_item_id', $sourceItemId)
                ->create();

            $sourceItems = $this->sourceItemRepository
                ->getList($searchCriteria)
                ->getItems();
            $sourceItem = reset($sourceItems);
            $this->sourceItemCache[$sourceItemId] = $sourceItem;
        }

        return (bool)$this->sourceItemCache[$sourceItemId];
    }

    /**
     * Get source item by source item ID.
     *
     * @param int $sourceItemId
     * @return SourceItemInterface
     * @throws NoSuchEntityException
     */
    private function getSourceItem($sourceItemId)
    {
        if (!$this->sourceItemExists($sourceItemId)) {
            throw new NoSuchEntityException(
                __('No source item found with ID %1', [$sourceItemId])
            );
        }

        return $this->sourceItemCache[$sourceItemId];
    }

    /**
     * Check if product exists using SKU.
     *
     * @param string $sku
     * @return bool
     */
    private function productExists($sku)
    {
        if (!isset($this->skuCache[$sku])) {
            try {
                $this->productRepository->get($sku);
                $this->skuCache[$sku] = true;
            } catch (NoSuchEntityException $e) {
                $this->skuCache[$sku] = false;
            }
        }

        return $this->skuCache[$sku];
    }

    /**
     * Load serial number objects using serial number values.
     *
     * @param array $serialnumbers
     * @return array
     */
    private function loadSerialnumbers($serialnumbers)
    {
        $result = [];

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('serialnumber', ['in' => $serialnumbers]);
        foreach ($collection as $item) {
            $result[$item->getSerialnumber()] = $item;
        }

        return $result;
    }

    /**
     * Log serial number creation/update.
     *
     * @param SerialnumberInterface $serialnumber
     * @return void
     */
    private function logSerialnumberChanges($serialnumber)
    {
        if (!$serialnumber->getId()) {
            $this->logManager->addLog(
                $serialnumber->getSerialnumber(),
                'Created serial number through import.'
            );
            return;
        }

        $this->logManager->addLogBySerialnumberId(
            (int)$serialnumber->getId(),
            'Updated serial number through import.'
        );

        foreach ($serialnumber->getData() as $field => $value) {
            if (!$serialnumber->dataHasChangedFor($field)) {
                continue;
            }

            switch ($field) {
                case SerialnumberInterface::IS_AVAILABLE:
                    $this->logManager->addLogBySerialnumberId(
                        (int)$serialnumber->getId(),
                        'Set status to %1',
                        [$value ? 'available' : 'unavailable']
                    );
                    break;
                case SerialnumberInterface::PURCHASE_PRICE:
                    $this->logManager->addLogBySerialnumberId(
                        (int)$serialnumber->getId(),
                        'Set purchase price to %1',
                        [$value]
                    );
                    break;
                case SerialnumberInterface::SOURCE_ITEM_ID:
                    $this->logSourceItemChange($serialnumber);
                    break;
            }
        }
    }

    /**
     * Log source item change.
     *
     * @param SerialnumberInterface $serialnumber
     * @return void
     */
    private function logSourceItemChange($serialnumber)
    {
        $oldSourceItemId = $serialnumber->getOrigData(SerialnumberInterface::SOURCE_ITEM_ID);
        $newSourceItemId = $serialnumber->getSourceItemId();
        if ((int)$oldSourceItemId === 0) {
            $sourceItem = $this->getSourceItem($newSourceItemId);
            $this->logManager->addLogBySerialnumberId(
                (int)$serialnumber->getId(),
                'Set serial number to source %1 and SKU %2.',
                [$sourceItem->getSourceCode(), $sourceItem->getSku()]
            );
            return;
        }

        $oldSourceItem = $this->getSourceItem($oldSourceItemId);
        $newSourceItem = $this->getSourceItem($newSourceItemId);

        $this->logManager->addLogBySerialnumberId(
            (int)$serialnumber->getId(),
            'Transferred serial number from SKU %1 and source %2 to SKU %3 and source %4.',
            [
                $oldSourceItem->getSku(),
                $oldSourceItem->getSourceCode(),
                $newSourceItem->getSku(),
                $newSourceItem->getSourceCode()
            ]
        );
    }

    /**
     * Prepare data for add/update.
     *
     * @param array $data
     * @return array
     */
    private function prepareData($data)
    {
        if (!isset($data['source_item_id']) &&
            isset($data['source_code']) &&
            isset($data['sku'])) {

            $data['source_item_id'] = $this->getSourceItemId(
                $data['sku'],
                $data['source_code']
            );
            unset($data['sku']);
            unset($data['source_code']);
        }

        $data['serialnumber'] = strtoupper($data['serialnumber']);
        $data = array_filter($data, function($value) {
            return $value !== null;
    });

        return $this->trimValues($data);
    }

    /**
     * Get source item ID using SKU and source code. Create if it doesn't exist.
     *
     * @param $sku
     * @param $sourceCode
     * @return int
     */
    private function getSourceItemId($sku, $sourceCode)
    {
        if (!isset($this->sourceItemCache[$sku . '-' . $sourceCode])) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(SourceItemInterface::SKU, $sku)
                ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
                ->create();

            $sourceItems = $this->sourceItemRepository
                ->getList($searchCriteria)
                ->getItems();

            if (empty($sourceItems)) {
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSku($sku);
                $sourceItem->setSourceCode($sourceCode);
                $sourceItem->setQuantity(0);
                $sourceItem->setStatus(SourceItemInterface::STATUS_OUT_OF_STOCK);
                $this->sourceItemsSave->execute([$sourceItem]);

                $sourceItems = $this->sourceItemRepository
                    ->getList($searchCriteria)
                    ->getItems();
            }

            $this->sourceItemCache[$sku . '-' . $sourceCode] = reset($sourceItems)->getId();
        }

        return $this->sourceItemCache[$sku . '-' . $sourceCode];
    }

    /**
     * Trim values with support for null values.
     *
     * @param array $data
     * @return array
     */
    private function trimValues($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = is_string($value) ? trim($value) : $value;
        }

        return $result;
    }
}
