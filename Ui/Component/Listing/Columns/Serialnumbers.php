<?php

namespace Zaahed\Serialnumber\Ui\Component\Listing\Columns;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Zaahed\Serialnumber\Action\IsProductTypeSupported;

class Serialnumbers extends Column
{
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    /**
     * @var AdminSession
     */
    private $adminSession;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var IsProductTypeSupported
     */
    private $getSupportedProductTypes;

    /**
     * @var Token|null
     */
    private $token = null;

    /**
     * @var string|null
     */
    private $baseUrl = null;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TokenFactory $tokenFactory
     * @param AdminSession $adminSession
     * @param UrlInterface $urlBuilder
     * @param IsProductTypeSupported $getSupportedProductTypes
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderItemRepositoryInterface $orderItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TokenFactory $tokenFactory,
        AdminSession $adminSession,
        UrlInterface $urlBuilder,
        IsProductTypeSupported $getSupportedProductTypes,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->tokenFactory = $tokenFactory;
        $this->adminSession = $adminSession;
        $this->urlBuilder = $urlBuilder;
        $this->getSupportedProductTypes = $getSupportedProductTypes;
    }

    /**
     * Prepare data source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $orderItemData = $this->getOrderItemData(
            $dataSource['data']['items']
        );

        foreach ($dataSource['data']['items'] as & $item) {
            $item[$this->getName()] = $orderItemData[$item['entity_id']] ?? [];
        }

        return $dataSource;
    }

    /**
     * Workaround because admin session-based authentication is not currently possible
     * according to the Magento documentation.
     *
     * Get API token for admin authentication.
     *
     * @return string
     * @see https://developer.adobe.com/commerce/webapi/get-started/authentication/gs-authentication-session/
     * @see https://github.com/magento/devdocs/pull/7393
     */
    private function getToken()
    {
        if ($this->token === null) {
            $this->token = $this->tokenFactory->create()
                ->createAdminToken($this->adminSession->getUser()->getId());
        }

        return $this->token->getToken();
    }

    /**
     * Get base URL.
     *
     * @return string|null
     */
    private function getBaseUrl()
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = $this->urlBuilder->getBaseUrl();
        }

        return $this->baseUrl;
    }

    /**
     * Get order item data with serial numbers.
     *
     * @param array $orderRows
     * @return array
     */
    private function getOrderItemData(array $orderRows)
    {
        $result = [];

        $orderItems = $this->getOrderItems($orderRows);
        $serialnumbers = $this->getSerialnumbers($orderItems);

        foreach ($orderItems as $item) {
            $result[$item->getOrderId()][] = [
                'item_id' => $item->getItemId(),
                'name' => $item->getName(),
                'qty' => $item->getQtyOrdered(),
                'options' => $this->getProductOptions($item),
                'serialnumbers' => $serialnumbers[$item->getItemId()] ?? [],
                'config' => $this->getApiConfig($item->getItemId())
            ];
        }

        return $result;

    }

    /**
     * Get product option values.
     *
     * @param OrderItemInterface $item
     * @return array
     */
    private function getProductOptions(OrderItemInterface $item)
    {
        $result = [];

        $productOptions = $item->getProductOptions();
        $options = $productOptions['options'] ?? [];
        foreach ($options as $option) {
            $result[] = $option['print_value'] ?? null;
        }

        // Remove null values from $result with array_filter.
        return array_filter($result);
    }

    /**
     * Get API config data.
     *
     * @param int|string $itemId
     * @return array
     */
    private function getApiConfig($itemId)
    {
        $apiUrl = sprintf(
            $this->getBaseUrl() .
            'rest/V1/serialnumber/order-item/%s/set-serialnumbers',
            $itemId
        );

        return [
            'token' => $this->getToken(),
            'api_url' => $apiUrl
        ];
    }

    /**
     * Get order items.
     *
     * @param array $orderRows
     * @return OrderItemInterface[]
     */
    private function getOrderItems(array $orderRows)
    {
        $supportedProductTypes = $this->getSupportedProductTypes->execute();
        $orderIds = array_map(function($row) {
            return $row['entity_id'];
        }, $orderRows);

        $criteria = $this->searchCriteriaBuilder
            ->addFilter(OrderItemInterface::ORDER_ID, $orderIds, 'in')
            ->addFilter(OrderItemInterface::PRODUCT_TYPE, $supportedProductTypes, 'in')
            ->addFilter(OrderItemInterface::PARENT_ITEM_ID, new \Zend_Db_Expr('null'), 'is')
            ->create();

        return $this->orderItemRepository->getList($criteria)->getItems();
    }

    /**
     * Get serial numbers for order item.
     *
     * @param OrderItemInterface[] $items
     * @return array
     */
    private function getSerialnumbers($items)
    {
        $result = [];

        foreach ($items as $item) {
            $serialnumbers = $item->getExtensionAttributes()->getSerialnumbers();
            if (empty($serialnumbers)) {
                continue;
            }

            foreach ($serialnumbers as $serialnumberItem) {
                $result[$item->getItemId()][] = $serialnumberItem->getSerialnumber();
            }
        }

        return $result;
    }
}
