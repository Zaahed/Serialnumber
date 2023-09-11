<?php

namespace Zaahed\Serialnumber\Plugin\Framework\Api\DataObjectHelper;

use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterfaceFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\Serialnumber;

class SetSerialnumbersOnSourceItem
{
    /**
     * @var SerialnumberInterfaceFactory
     */
    private $serialnumberFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param SerialnumberInterfaceFactory $serialnumberFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        SerialnumberInterfaceFactory $serialnumberFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->serialnumberFactory = $serialnumberFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Set serial numbers on source item (extension attribute).
     *
     * @param DataObjectHelper $subject
     * @param mixed $dataObject
     * @param array $data
     * @param string $interfaceName
     * @return void
     */
    public function beforePopulateWithArray(
        DataObjectHelper $subject,
        $dataObject,
        array $data,
        $interfaceName
    ) {
        if (!($dataObject instanceof SourceItemInterface)) {
            return;
        }
        if (empty($data['serialnumbers']) || !is_scalar($data['serialnumbers'])) {
            $serialnumbers = [];
        }
        else {
            $entityId = $data['entity_id'] ?? null;
            $serialnumbers = $this->getSerialnumberItems($data['serialnumbers'], $entityId);
        }

        $dataObject->getExtensionAttributes()->setSerialnumbers($serialnumbers);
    }

    /**
     * Convert array of serial number strings to Serialnumber objects.
     *
     * @param mixed $serialnumbers
     * @param int|null $sourceItemId
     * @return Serialnumber[]
     */
    private function getSerialnumberItems($serialnumbers, $sourceItemId)
    {
        $serialnumberItems = [];
        $serialnumbers = is_array($serialnumbers) ?
            $serialnumbers :
            preg_split('/\r\n|\r|\n/', trim((string)$serialnumbers));

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('serialnumber', ['in' => $serialnumbers]);
        foreach ($collection as $item) {
            /** @var Serialnumber $item */
            $serialnumberItems[$item->getSerialnumber()] = $item;
        }

        foreach ($serialnumbers as $serialnumber) {
            if (in_array($serialnumber, $serialnumberItems)) {
                continue;
            }

            $item = $this->serialnumberFactory->create();
            $item->setSerialnumber($serialnumber);
            $item->setIsAvailable(true);

            if ($sourceItemId !== null) {
                $item->setSourceItemId($sourceItemId);
            }

            $serialnumberItems[$serialnumber] = $item;
        }

        return array_values($serialnumberItems);
    }
}
