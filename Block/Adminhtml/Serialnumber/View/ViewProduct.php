<?php
declare(strict_types=1);


namespace Zaahed\Serialnumber\Block\Adminhtml\Serialnumber\View;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterfaceTest;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Zaahed\Serialnumber\Api\SerialnumberRepositoryInterface;

class ViewProduct implements ButtonProviderInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SerialnumberRepositoryInterface
     */
    private $serialnumberRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param RequestInterface $request
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param SerialnumberRepositoryInterface $serialnumberRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        RequestInterface $request,
        SourceItemRepositoryInterface $sourceItemRepository,
        ProductRepositoryInterface $productRepository,
        SerialnumberRepositoryInterface $serialnumberRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        UrlInterface $urlBuilder
    ) {
        $this->request = $request;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->productRepository = $productRepository;
        $this->serialnumberRepository = $serialnumberRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        $productViewUrl = $this->getProductViewUrl();
        if ($productViewUrl === null) {
            return [];
        }

        return [
            'label' => __('View Product'),
            'class' => 'view_product',
            'sort_order' => 20,
            'on_click' => sprintf("location.href = '%s';", $productViewUrl),
        ];
    }

    /**
     * Get product view url.
     *
     * @return string|null
     */
    private function getProductViewUrl()
    {
        $serialnumberId = $this->request->getParam('id');
        $serialnumber = $this->serialnumberRepository->getById((int)$serialnumberId);
        $sourceItemId = $serialnumber->getSourceItemId();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('source_item_id', $sourceItemId)
            ->create();
        /** @var SourceItemInterface[] $sourceItems */
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        if (empty($sourceItems)) {
            return null;
        }

        $sku = reset($sourceItems)->getSku();
        try {
            $product = $this->productRepository->get($sku);
        }
        catch (NoSuchEntityException $e) {
            return null;
        }

        $productId = $product->getId();
        return $this->urlBuilder->getUrl('catalog/product/edit', ['id' => $productId]);
    }

}
