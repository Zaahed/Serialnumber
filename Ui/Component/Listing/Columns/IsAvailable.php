<?php

namespace Zaahed\Serialnumber\Ui\Component\Listing\Columns;

class IsAvailable implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 1,
                'label' => __('Yes')
            ],
            [
                'value' => 0,
                'label' => __('No')
            ]
        ];
    }
}