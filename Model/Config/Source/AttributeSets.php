<?php

namespace Zaahed\Serialnumber\Model\Config\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

class AttributeSets implements OptionSourceInterface
{
    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    /**
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TypeFactory $typeFactory
     */
    public function __construct(
        AttributeSetRepositoryInterface $attributeSetRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TypeFactory $typeFactory
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->typeFactory = $typeFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $result = [];

        $entityTypeId = $this->typeFactory->create()
            ->loadByCode(Product::ENTITY)
            ->getEntityTypeId();
        $this->searchCriteriaBuilder
            ->addFilter('entity_type_id', $entityTypeId);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributeSets = $this->attributeSetRepository
            ->getList($searchCriteria)
            ->getItems();

        foreach ($attributeSets as $attributeSet) {
            $result[] = [
                'value' => $attributeSet->getAttributeSetId(),
                'label' => $attributeSet->getAttributeSetName()
            ];
        }

        return $result;
    }
}