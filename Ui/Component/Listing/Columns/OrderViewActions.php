<?php

namespace Zaahed\Serialnumber\Ui\Component\Listing\Columns;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class OrderViewActions extends \Magento\Ui\Component\Listing\Columns\Column
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
     * Add exchange serial number action for opening a modal.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getName()] = [
                    'exchange' => [
                        'label' => __('Exchange'),
                        'class' => 'exchange-action'
                    ],
                    'view-serialnumber' => [
                        'label' => __('View Serial Number'),
                        'href' => $this->urlBuilder->getUrl(
                            'catalog/serialnumber/view',
                            ['id' => $item['serialnumber_id']]
                        )
                    ]
                ];

                if ($item['product_id'] !== null) {
                    $item[$this->getName()]['view-product'] = [
                        'label' => __('View Product'),
                        'href'  => $this->urlBuilder->getUrl(
                            'catalog/product/edit',
                            ['id' => $item['product_id']]
                        )
                    ];
                }
            }
        }

        return $dataSource;
    }
}