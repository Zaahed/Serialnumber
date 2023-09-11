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
use Zaahed\Serialnumber\Api\Data\SerialnumberInterfaceFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory as SerialnumberCollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log\SaveMultiple as SaveMultipleLogEntries;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber;
use Zaahed\Serialnumber\Model\Serialnumber\LogFactory;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class ImportSerialnumbersFromDor implements DataPatchInterface
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
     * @var SerialnumberInterfaceFactory
     */
    private $serialnumberFactory;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var SerialnumberCollectionFactory
     */
    private $serialnumberCollectionFactory;

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
     * @param SerialnumberInterfaceFactory $serialnumberFactory
     * @param SaveMultiple $saveMultiple
     * @param SerialnumberCollectionFactory $serialnumberCollectionFactory
     * @param DorCollectionFactory $dorCollectionFactory
     * @param Client $client
     * @param LogFactory $logFactory
     * @param SaveMultipleLogEntries $saveMultipleLogEntries
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SerialnumberInterfaceFactory $serialnumberFactory,
        SaveMultiple $saveMultiple,
        SerialnumberCollectionFactory $serialnumberCollectionFactory,
        DorCollectionFactory $dorCollectionFactory,
        Client $client,
        LogFactory $logFactory,
        SaveMultipleLogEntries $saveMultipleLogEntries
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->dorCollectionFactory = $dorCollectionFactory;
        $this->serialnumberFactory = $serialnumberFactory;
        $this->saveMultiple = $saveMultiple;
        $this->client = $client;
        $this->serialnumberCollectionFactory = $serialnumberCollectionFactory;
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

        $serialnumbersToSave = [];
        $dorCollection = $this->dorCollectionFactory->create();
        $currentSerialnumbers = $this->serialnumberCollectionFactory
            ->create()
            ->addFieldToFilter(
                'serialnumber',
                ['in' => $dorCollection->getColumnValues('serialNumber')]
            )
            ->getColumnValues('serialnumber');

        foreach ($dorCollection as $item) {
            $serialnumber = $item->getData('serialNumber') ??
                $item->getData('imeiNumber');
            $serialnumber = strtoupper($serialnumber);
            if (in_array($serialnumber, $currentSerialnumbers)) {
                continue; // Skip because purchase price is already imported.
            }

            sleep(5); // To avoid 429 exceptions and complaints from DOR.
            $response = $this->client->product->getProduct(
                $item->getDorId()
            );
            $purchasePrice = $response['product']['purchasePrice'];

            $serialnumbersToSave[$serialnumber] = $this->serialnumberFactory
                ->create()
                ->setSerialnumber($serialnumber)
                ->setPurchasePrice($purchasePrice)
                ->setIsAvailable(true);
        }

        $this->saveMultiple->execute(array_values($serialnumbersToSave));
        $this->createLogEntries($serialnumbersToSave);

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
        $valueIdMap = [];
        $serialnumberCollection = $this->serialnumberCollectionFactory
            ->create()
            ->addFieldToFilter('serialnumber',
                ['in' => array_keys($serialnumbers)]);

        foreach ($serialnumberCollection as $item) {
            $valueIdMap[$item->getSerialnumber()] = $item->getEntityId();
        }

        $logEntriesToSave = [];
        foreach ($serialnumbers as $serialnumber) {
            $logEntry = $this->logFactory->create();
            $logEntry->setSerialnumberId(
                (int)$valueIdMap[$serialnumber->getSerialnumber()]
            );
            $logEntry->setMessage('Imported serial number from DOR.');
            $logEntriesToSave[] = $logEntry;
        }

        $this->saveMultipleLogEntries->execute($logEntriesToSave);
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
        return [ImportPurchasePriceFromDor::class];
    }
}
