<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Zaahed\Serialnumber\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Zaahed\Dor\Model\Api\Client;
use Zaahed\Dor\Model\ResourceModel\Registration\CollectionFactory as DorCollectionFactory;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory as SerialnumberCollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log\SaveMultiple as SaveMultipleLogEntries;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber;
use Zaahed\Serialnumber\Model\Serialnumber\LogFactory;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class ImportPurchasePriceFromDor implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var DorCollectionFactory
     */
    private $dorCollectionFactory;

    /**
     * @var SerialnumberCollectionFactory
     */
    private $serialnumberCollectionFactory;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var LogFactory
     */
    private $logFactory;

    /**
     * @var SaveMultipleLogEntries
     */
    private $saveMultipleLogEntries;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param DorCollectionFactory $dorCollectionFactory
     * @param SerialnumberCollectionFactory $serialnumberCollectionFactory
     * @param SaveMultiple $saveMultiple
     * @param Client $client
     * @param LogFactory $logFactory
     * @param SaveMultipleLogEntries $saveMultipleLogEntries
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        DorCollectionFactory $dorCollectionFactory,
        SerialnumberCollectionFactory $serialnumberCollectionFactory,
        SaveMultiple $saveMultiple,
        Client $client,
        LogFactory $logFactory,
        SaveMultipleLogEntries $saveMultipleLogEntries
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->dorCollectionFactory = $dorCollectionFactory;
        $this->client = $client;
        $this->serialnumberCollectionFactory = $serialnumberCollectionFactory;
        $this->saveMultiple = $saveMultiple;
        $this->logFactory = $logFactory;
        $this->saveMultipleLogEntries = $saveMultipleLogEntries;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $purchasePriceData = $this->getSerialnumberPurchasePriceData();

        /** @var Serialnumber[] $serialnumbers */
        $serialnumbers = $this->serialnumberCollectionFactory
            ->create()
            ->addFieldToFilter(SerialnumberInterface::SERIALNUMBER,
                ['in' => array_keys($purchasePriceData)])
            ->getItems();

        foreach ($serialnumbers as $serialnumber) {
            $serialnumber->setPurchasePrice(
                (float)$purchasePriceData[$serialnumber->getSerialnumber()]
            );
        }

        $this->saveMultiple->execute($serialnumbers);
        $this->createLogEntries($serialnumbers);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Create log entry for each serial number.
     *
     * @param Serialnumber[] $serialnumbers
     * @return void
     */
    private function createLogEntries(array $serialnumbers)
    {
        $logEntriesToSave = [];
        foreach ($serialnumbers as $serialnumber) {
            $logEntry = $this->logFactory->create();
            $logEntry->setSerialnumberId((int)$serialnumber->getEntityId());
            $logEntry->setMessage('Imported purchase price from DOR.');
            $logEntriesToSave[] = $logEntry;
        }

        $this->saveMultipleLogEntries->execute($logEntriesToSave);
    }

    /**
     * Get the purchase price for each serial number registered in the DOR.
     *
     * @return array
     */
    private function getSerialnumberPurchasePriceData()
    {
        $result = [];
        $dorCollection = $this->dorCollectionFactory->create();
        foreach ($dorCollection as $item) {
            sleep(5); // To avoid 429 exceptions and complaints from DOR.
            $serialnumber = $item->getData('serialNumber') ??
                $item->getData('imeiNumber');
            $serialnumber = strtoupper($serialnumber);
            $response = $this->client->product->getProduct(
                $item->getDorId()
            );
            $purchasePrice = $response['product']['purchasePrice'];
            $result[$serialnumber] = $purchasePrice;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [ImportSerialnumbersFromOldModule::class];
    }
}
