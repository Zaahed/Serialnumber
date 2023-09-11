<?php

namespace Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem\Validator;

use Magento\Framework\Phrase;
use Magento\Framework\Validation\ValidationResult;

interface ValidatorInterface
{
    /**
     * Validate before setting the serial numbers on the order item.
     *
     * @param int $itemId
     * @param array $serialnumbers
     * @return string[]
     */
    public function validate(int $itemId, array $serialnumbers): array;
}