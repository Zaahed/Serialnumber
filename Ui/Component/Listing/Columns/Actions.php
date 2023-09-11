<?php

namespace Zaahed\Serialnumber\Ui\Component\Listing\Columns;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class Actions extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Add actions.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getName('name')]['view'] = [
                    'href' => sprintf('view/id/%s', $item['entity_id']),
                    'label' => __('View')
                ];

                if ($item['product_id'] !== null) {
                    $item[$this->getData('name')]['view_product'] = [
                        'href' => $this->urlBuilder->getUrl('catalog/product/edit/',
                            ['id' => $item['product_id']]),
                        'label' => __('View Product')
                    ];
                }
            }
        }

        return $dataSource;
    }
}