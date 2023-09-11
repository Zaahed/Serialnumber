<?php

namespace Zaahed\Serialnumber\Model\Config\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use Zaahed\Serialnumber\Model\Config\PdfLabel as Config;

class ProductAttributes implements OptionSourceInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Config $config
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Config $config,
        CollectionFactory $collectionFactory
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->config = $config;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $result = [];

        $this->filterByAttributeSets();
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributes = $this->attributeRepository
            ->getList(Product::ENTITY, $searchCriteria)
            ->getItems();

        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            $label = $attribute->getDefaultFrontendLabel();
            $result[] = [
                'value' => $code,
                'label' => sprintf('%s (%s)', $label, $code)
            ];
        }

        return $result;
    }

    /**
     * Filter attributes by selected attribute sets in config.
     *
     * @return void
     */
    private function filterByAttributeSets()
    {
        $attributeSetIds = $this->config->getAttributeSets();
        $collection = $this->collectionFactory->create();
        $collection->setAttributeSetsFilter($attributeSetIds);

        $attributeIds = $collection->getColumnValues('attribute_id');
        $attributeIds = array_unique($attributeIds);

        if (!empty($attributeIds)) {
            $this->searchCriteriaBuilder->addFilter('attribute_id',
                $attributeIds, 'in');
        }
    }
}