<?php
declare(strict_types=1);


namespace Zaahed\Serialnumber\Action;

class IsProductTypeSupported
{
    /**
     * @var array
     */
    private $productTypes;

    /**
     * @param array $productTypes
     */
    public function __construct(array $productTypes = [])
    {
        $this->productTypes = $productTypes;
    }

    /**
     * Check if product type is supported.
     *
     * @param string $productType
     * @return bool
     */
    public function execute(string $productType): bool
    {
        return in_array($productType, $this->productTypes);
    }
}
