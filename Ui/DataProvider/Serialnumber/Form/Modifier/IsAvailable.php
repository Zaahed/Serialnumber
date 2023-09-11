<?php

namespace Zaahed\Serialnumber\Ui\DataProvider\Serialnumber\Form\Modifier;

use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;

class IsAvailable implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{

    /**
     * @inheritDoc
     */
    public function modifyData(array $data)
    {
        foreach ($data['items'] as & $item) {
            $item[SerialnumberInterface::IS_AVAILABLE] =
                $item[SerialnumberInterface::IS_AVAILABLE] ? __('Yes') : __('No');
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
