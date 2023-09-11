<?php

namespace Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem\Validator;

class IsAlphanumeric implements ValidatorInterface
{

    /**
     * @inheritDoc
     */
    public function validate(int $itemId, array $serialnumbers): array
    {
        foreach ($serialnumbers as $serialnumber) {
            if (ctype_alnum($serialnumber)) {
                continue;
               }
            return [__('One or more serial number(s) contain invalid characters. Only letters and numbers are allowed.')];
        }

        return [];
    }
}