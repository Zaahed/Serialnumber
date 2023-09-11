<?php

namespace Zaahed\Serialnumber\Plugin\Dor\Api\RegistrationManagementInterface;

use Magento\Framework\Serialize\Serializer\Json;
use Zaahed\Dor\Api\RegistrationManagementInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber;

class UpdateSerialnumbers
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Json $json
     * @param SaveMultiple $saveMultiple
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Json $json,
        SaveMultiple $saveMultiple
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->json = $json;
        $this->saveMultiple = $saveMultiple;
    }

    /**
     * Update serial numbers after updating registration in DOR.
     *
     * @param RegistrationManagementInterface $subject
     * @param array $result
     * @param $entityId
     * @param $registration
     * @return array
     */
    public function afterUpdate(
        RegistrationManagementInterface $subject,
        array $result,
        $entityId,
        $registration
    ) {
        if (is_string($registration)) {
            $registration = $this->json->unserialize($registration);
        }

        $serialnumbersToSave = [];
        $serialnumbers = $registration['serialNumber'] ??
            $registration['imeiNumber'];
        $purchasePrice = (float)$registration['purchasePrice'];

        $collection = $this->collectionFactory
            ->create()
            ->addFieldToFilter('serialnumber', ['in' => $serialnumbers]);

        /** @var Serialnumber $item */
        foreach ($collection as $item) {
            $item->setPurchasePrice($purchasePrice);
            $serialnumbersToSave[] = $item;
        }

        $this->saveMultiple->execute($serialnumbersToSave);

        return $result;
    }
}