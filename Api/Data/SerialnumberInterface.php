<?php

namespace Zaahed\Serialnumber\Api\Data;

interface SerialnumberInterface
{
    /**#@+
     * Constants defined for keys of data array.
     */
    const SERIALNUMBER = 'serialnumber';

    const SOURCE_ITEM_ID = 'source_item_id';

    const PURCHASE_PRICE = 'purchase_price';

    const IS_AVAILABLE = 'is_available';
    /**#@-*/

    /**
     * Get serial number.
     *
     * @return string|null
     */
    public function getSerialnumber(): ?string;

    /**
     * Set serial number.
     *
     * @param string $serialnumber
     * @return SerialnumberInterface
     */
    public function setSerialnumber(string $serialnumber): SerialnumberInterface;

    /**
     * Get source item ID.
     *
     * @return int|null
     */
    public function getSourceItemId(): ?int;

    /**
     * Set source item ID.
     *
     * @param int|null $itemId
     * @return SerialnumberInterface
     */
    public function setSourceItemId(?int $itemId): SerialnumberInterface;

    /**
     * Get purchase price.
     *
     * @return float|null
     */
    public function getPurchasePrice(): ?float;

    /**
     * Set purchase price.
     *
     * @param float|null $purchasePrice
     * @return SerialnumberInterface
     */
    public function setPurchasePrice(?float $purchasePrice): SerialnumberInterface;

    /**
     * Is serial number available.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Set serial number availability status.
     *
     * @param bool $isAvailable
     * @return SerialnumberInterface
     */
    public function setIsAvailable(bool $isAvailable): SerialnumberInterface;
}
