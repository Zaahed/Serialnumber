<?php

namespace Zaahed\Serialnumber\Action;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as SerialnumberResource;
use Zaahed\Serialnumber\Model\SerialnumberFactory;

class SetSourceItemForSerialnumber
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SerialnumberFactory
     */
    private $serialnumberFactory;

    /**
     * @var SerialnumberResource
     */
    private $serialnumberResource;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SerialnumberFactory $serialnumberFactory
     * @param SerialnumberResource $serialnumberResource
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        SerialnumberFactory $serialnumberFactory,
        SerialnumberResource $serialnumberResource
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->serialnumberFactory = $serialnumberFactory;
        $this->serialnumberResource = $serialnumberResource;
    }

    /**
     * Set source item for serial number using SKU and source code.
     *
     * @param int $serialnumberId
     * @param string $sku
     * @param string $sourceCode
     * @param array $serialnumberData
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(
        int $serialnumberId,
        string $sku,
        string $sourceCode,
        array $serialnumberData = []
    ) {

        $this->searchCriteriaBuilder->addFilter(SourceItemInterface::SKU, $sku);
        $this->searchCriteriaBuilder->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $item = null;
        foreach ($sourceItems as $i) {
            $item = $i;
            break;
        }
        if ($item === null) {
            $item = $this->sourceItemFactory->create();
            $item->setSku($sku);
            $item->setSourceCode($sourceCode);
            $item->setQuantity(0);
            $item->setStatus(SourceItemInterface::STATUS_OUT_OF_STOCK);
        }

        $serialnumber = $this->serialnumberFactory->create();
        $this->serialnumberResource->load($serialnumber, $serialnumberId);
        if ($serialnumber->getId() === null) {
            throw new NoSuchEntityException(
                __('No serial number found with ID %1.', [$serialnumberId])
            );
        }
        $serialnumber->addData($serialnumberData);

        $serialnumbers = $item->getExtensionAttributes()->getSerialnumbers() ?? [];
        $serialnumbers[] = $serialnumber;
        $item->getExtensionAttributes()->setSerialnumbers($serialnumbers);

        $this->sourceItemsSave->execute([$item]);
    }
}
