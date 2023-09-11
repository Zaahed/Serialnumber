<?php

namespace Zaahed\Serialnumber\Ui\Component\Listing\Columns;

use Magento\InventoryApi\Api\SourceRepositoryInterface;

class SourceCode implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(SourceRepositoryInterface $sourceRepository)
    {
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $result = [];
        $sources = $this->sourceRepository->getList()->getItems();

        foreach ($sources as $source) {
            $result[] = [
                'value' => $source->getSourceCode(),
                'label' => $source->getName()
            ];
        }

        return $result;
    }
}