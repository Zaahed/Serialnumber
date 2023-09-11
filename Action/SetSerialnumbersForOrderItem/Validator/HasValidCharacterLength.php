<?php

namespace Zaahed\Serialnumber\Action\SetSerialnumbersForOrderItem\Validator;

class HasValidCharacterLength implements ValidatorInterface
{
    const MAX_LENGTH = 50;

    /**
     * @inheritDoc
     */
    public function validate(int $itemId, array $serialnumbers): array
    {
        foreach ($serialnumbers as $serialnumber) {
            if (strlen($serialnumber) > self::MAX_LENGTH) {
                return [sprintf(
                            'One or more serial numbers exceed the maximum character length (%s).',
                            self::MAX_LENGTH
                        )];
            }
        }

        return [];
    }
}