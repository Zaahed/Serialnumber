<?php

namespace Zaahed\Serialnumber\Model;

use Magento\Framework\Model\AbstractModel;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as ResourceModel;

class Serialnumber extends AbstractModel implements SerialnumberInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'serialnumber_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get serial number.
     *
     * @return string|null
     */
    public function getSerialnumber(): ?string
    {
        $serialnumber = $this->getData(SerialnumberInterface::SERIALNUMBER);
        return is_string($serialnumber) ? strtoupper($serialnumber) : null;
    }

    /**
     * Set serial number.
     *
     * @param string $serialnumber
     * @return SerialnumberInterface
     */
    public function setSerialnumber(string $serialnumber): SerialnumberInterface
    {
        $this->setData(SerialnumberInterface::SERIALNUMBER, strtoupper($serialnumber));
        return $this;
    }

    /**
     * Get source item ID.
     *
     * @return int|null
     */
    public function getSourceItemId(): ?int
    {
        return $this->getData(SerialnumberInterface::SOURCE_ITEM_ID);
    }

    /**
     * Set source item ID.
     *
     * @param int|null $itemId
     * @return SerialnumberInterface
     */
    public function setSourceItemId(?int $itemId): SerialnumberInterface
    {
        $itemId = $itemId === 0 ? null : $itemId;
        $this->setData(SerialnumberInterface::SOURCE_ITEM_ID, $itemId);
        return $this;
    }

    /**
     * Get purchase price.
     *
     * @return float|null
     */
    public function getPurchasePrice(): ?float
    {
        return $this->getData(SerialnumberInterface::PURCHASE_PRICE) !== null
            ? (float)$this->getData(SerialnumberInterface::PURCHASE_PRICE) : null;
    }

    /**
     * Set purchase price.
     *
     * @param float|null $purchasePrice
     * @return SerialnumberInterface
     */
    public function setPurchasePrice(?float $purchasePrice): SerialnumberInterface
    {
        $this->setData(SerialnumberInterface::PURCHASE_PRICE, $purchasePrice);
        return $this;
    }

    /**
     * Is serial number available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return (bool)$this->getData(SerialnumberInterface::IS_AVAILABLE);
    }

    /**
     * Set serial number availability status.
     *
     * @param bool $isAvailable
     * @return SerialnumberInterface
     */
    public function setIsAvailable(bool $isAvailable): SerialnumberInterface
    {
        $this->setData(SerialnumberInterface::IS_AVAILABLE, $isAvailable);
        return $this;
    }
}
