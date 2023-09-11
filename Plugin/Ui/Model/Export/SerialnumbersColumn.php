<?php

namespace Zaahed\Serialnumber\Plugin\Ui\Model\Export;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Ui\Model\Export\MetadataProvider;

class SerialnumbersColumn
{
   /*
    * Serial numbers field.
    */
    const SERIALNUMBERS_FIELD = 'serialnumbers';

    /**
     * Component instance name.
     */
    private $componentInstanceName;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Set component instance name.
     *
     * @param MetadataProvider $subject
     * @param array $result
     * @param UiComponentInterface $component
     * @return string[]
     */
    public function afterGetHeaders(
        MetadataProvider $subject,
        array $result,
        UiComponentInterface $component
    ) {
        $this->componentInstanceName = $component->getName();
        return $result;
    }

    /**
     * Add serial numbers to column.
     *
     * @param MetadataProvider $subject
     * @param DocumentInterface $document
     * @param array $fields
     * @param array $options
     * @return array|void
     */
    public function beforeGetRowData(
        MetadataProvider $subject,
        DocumentInterface $document,
        array $fields,
        array $options
    ) {
        if ($this->componentInstanceName !== 'sales_order_grid') {
            return;
        }

        $order = $this->orderRepository->get(
            $document->getData('entity_id')
        );
        $items = $order->getItems();
        $output = '';

        foreach ($items as $index => $item) {
            $serialnumbers = $item->getExtensionAttributes()->getSerialnumbers();
            if ($serialnumbers === null) {
                continue;
            }
            $serialnumbers = array_map(function($item) {
                return $item->getSerialnumber();
            }, $serialnumbers);

            $name = sprintf('%s (%s)', $item->getName(), $item->getSku());
            $output .= sprintf("%s\n%s%s",
                $name,
                implode("\n", $serialnumbers),
                $index === array_key_last($items) ? '' : "\n\n");
        }

        $document->setCustomAttribute(
            self::SERIALNUMBERS_FIELD,
            $output
        );

        return [$document, $fields, $options];
    }
}
