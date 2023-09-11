<?php

namespace Zaahed\Serialnumber\Plugin\Dor\Api\RegistrationManagementInterface;

use Magento\Framework\Serialize\Serializer\Json;
use Zaahed\Dor\Api\RegistrationManagementInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterfaceFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log\SaveMultiple as SaveMultipleLogEntries;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber;
use Zaahed\Serialnumber\Model\Serialnumber\LogFactory;

class CreateSerialnumbers
{
    /**
     * @var SerialnumberInterfaceFactory
     */
    private $serialnumberFactory;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LogFactory
     */
    private $logFactory;

    /**
     * @var SaveMultipleLogEntries
     */
    private $saveMultipleLogEntries;

    /**
     * @param SerialnumberInterfaceFactory $serialnumberFactory
     * @param SaveMultiple $saveMultiple
     * @param Json $json
     * @param CollectionFactory $collectionFactory
     * @param LogFactory $logFactory
     * @param SaveMultipleLogEntries $saveMultipleLogEntries
     */
    public function __construct(
        SerialnumberInterfaceFactory $serialnumberFactory,
        SaveMultiple $saveMultiple,
        Json $json,
        CollectionFactory $collectionFactory,
        LogFactory $logFactory,
        SaveMultipleLogEntries $saveMultipleLogEntries
    ) {
        $this->serialnumberFactory = $serialnumberFactory;
        $this->saveMultiple = $saveMultiple;
        $this->json = $json;
        $this->collectionFactory = $collectionFactory;
        $this->logFactory = $logFactory;
        $this->saveMultipleLogEntries = $saveMultipleLogEntries;
    }

    /**
     * Create new serial numbers after creating registration in DOR.
     *
     * @param RegistrationManagementInterface $subject
     * @param array $result
     * @param $registration
     * @return array
     */
    public function afterCreate(
        RegistrationManagementInterface $subject,
        array $result,
        $registration
    ) {
        $serialnumbersToSave = [];

        if (is_string($registration)) {
            $registration = $this->json->unserialize($registration);
        }

        $serialnumbers = $registration['serialNumber'] ??
            $registration['imeiNumber'];

        foreach ($serialnumbers as $serialnumber) {
            $item = $this->serialnumberFactory->create();
            $item->setSerialnumber($serialnumber);
            $item->setPurchasePrice($registration['price']);
            $item->setIsAvailable(true);
            $serialnumbersToSave[] = $item;
        }

        $this->saveMultiple->execute($serialnumbersToSave);
        $this->createLogEntries($serialnumbersToSave);

        return $result;
    }

    /**
     * Create a log entry for each serial number.
     *
     * @param Serialnumber[] $serialnumbers
     * @return void
     */
    private function createLogEntries(array $serialnumbers)
    {
        $newSerialnumberIds = [];
        $buybackSerialnumberIds = [];
        $logEntriesToSave = [];

        $serialnumberValues = array_map(function($item) {
            return $item->getSerialnumber();
        }, $serialnumbers);
        $serialnumberValueIdMap = $this->getSerialnumberValueIdMap($serialnumberValues);

        foreach ($serialnumbers as $serialnumber) {
            if ($serialnumber->getEntityId() === null) {
                $newSerialnumberIds[] =
                    $serialnumberValueIdMap[$serialnumber->getSerialnumber()];
            } else {
                $buybackSerialnumberIds[] =
                    $serialnumberValueIdMap[$serialnumber->getSerialnumber()];
            }
        }

        foreach ($newSerialnumberIds as $id) {
            $logEntry = $this->logFactory->create();
            $logEntry->setSerialnumberId($id);
            $logEntry->setMessage('Created serial number from DOR registration.');
            $logEntriesToSave[] = $logEntry;
        }

        foreach ($buybackSerialnumberIds as $id) {
            $logEntry = $this->logFactory->create();
            $logEntry->setSerialnumberId($id);
            $logEntry->setMessage('Updated serial number from DOR registration.');
            $logEntriesToSave[] = $logEntry;
        }

        $this->saveMultipleLogEntries->execute($logEntriesToSave);
    }

    /**
     * Get serial numbers with serial number as key and ID as value.
     *
     * @param array $serialnumbers
     * @return array
     */
    private function getSerialnumberValueIdMap(array $serialnumbers)
    {
        $result = [];

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(SerialnumberInterface::SERIALNUMBER,
            ['in' => $serialnumbers]);
        foreach ($collection as $item) {
            $result[$item->getSerialnumber()] = $item->getEntityId();
        }

        return $result;
    }
}
