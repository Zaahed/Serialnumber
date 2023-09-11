<?php

namespace Zaahed\Serialnumber\Ui\Component\Listing\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class SerialnumberLog extends DataProvider
{
    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = parent::getData();
        foreach ($data['items'] as & $item) {
            $item['message'] = __(
                $item['message'],
                json_decode($item['message_values'])
            );
        }

        return $data;
    }
}