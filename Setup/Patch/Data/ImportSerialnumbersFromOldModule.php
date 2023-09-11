<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Zaahed\Serialnumber\Setup\Patch\Data;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterfaceFactory;
use Zaahed\Serialnumber\Model\Creditmemo\Item\Serialnumber;
use Zaahed\Serialnumber\Model\Creditmemo\Item\SerialnumberFactory as CreditmemoItemSerialnumberFactory;
use Zaahed\Serialnumber\Model\Order\Item\SerialnumberFactory as OrderItemSerialnumberFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Creditmemo\Item\Serialnumber\SaveMultiple as SaveMultipleCreditmemoItemSerialnumbers;
use Zaahed\Serialnumber\Model\ResourceModel\Order\Item\Serialnumber\SaveMultiple as SaveMultipleOrderItemSerialnumbers;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Collection as SerialnumberCollection;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\CollectionFactory as SerialnumberCollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\Log\SaveMultiple as SaveMultipleLogEntries;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber\SaveMultiple as SaveMultipleSerialnumbers;
use Zaahed\Serialnumber\Model\ResourceModel\Shipment\Item\Serialnumber\SaveMultiple as SaveMultipleShipmentItemSerialnumbers;
use Zaahed\Serialnumber\Model\Serialnumber\LogFactory;
use Zaahed\Serialnumber\Model\Shipment\Item\SerialnumberFactory as ShipmentItemSerialnumberFactory;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class ImportSerialnumbersFromOldModule implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var SerialnumberInterfaceFactory
     */
    private $serialnumberFactory;

    /**
     * @var SerialnumberCollectionFactory
     */
    private $serialnumberCollectionFactory;

    /**
     * @var SaveMultipleSerialnumbers
     */
    private $saveMultipleSerialnumbers;

    /**
     * @var OrderItemCollectionFactory
     */
    private $orderItemCollectionFactory;

    /**
     * @var OrderItemSerialnumberFactory
     */
    private $orderItemSerialnumberFactory;

    /**
     * @var SaveMultipleOrderItemSerialnumbers
     */
    private $saveMultipleOrderItemSerialnumbers;

    /**
     * @var ShipmentItemSerialnumberFactory
     */
    private $shipmentItemSerialnumberFactory;

    /**
     * @var SaveMultipleShipmentItemSerialnumbers
     */
    private $saveMultipleShipmentItemSerialnumbers;

    /**
     * @var CreditmemoItemSerialnumberFactory
     */
    private $creditmemoItemSerialnumberFactory;

    /**
     * @var SaveMultipleCreditmemoItemSerialnumbers
     */
    private $saveMultipleCreditmemoItemSerialnumbers;

    /**
     * @var LogFactory
     */
    private $logFactory;

    /**
     * @var SaveMultipleLogEntries
     */
    private $saveMultipleLogEntries;

    /**
     * @var array|null
     */
    private $valueIdMap = null;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SerialnumberCollectionFactory $serialnumberCollectionFactory
     * @param SerialnumberInterfaceFactory $serialnumberFactory
     * @param SaveMultipleSerialnumbers $saveMultipleSerialnumbers
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param OrderItemSerialnumberFactory $orderItemSerialnumberFactory
     * @param SaveMultipleOrderItemSerialnumbers $saveMultipleOrderItemSerialnumbers
     * @param ShipmentItemSerialnumberFactory $shipmentItemSerialnumberFactory
     * @param SaveMultipleShipmentItemSerialnumbers $saveMultipleShipmentItemSerialnumbers
     * @param CreditmemoItemSerialnumberFactory $creditmemoItemSerialnumberFactory
     * @param SaveMultipleCreditmemoItemSerialnumbers $saveMultipleCreditmemoItemSerialnumbers
     * @param LogFactory $logFactory
     * @param SaveMultipleLogEntries $saveMultipleLogEntries
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SerialnumberCollectionFactory $serialnumberCollectionFactory,
        SerialnumberInterfaceFactory $serialnumberFactory,
        SaveMultipleSerialnumbers $saveMultipleSerialnumbers,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        OrderItemSerialnumberFactory $orderItemSerialnumberFactory,
        SaveMultipleOrderItemSerialnumbers $saveMultipleOrderItemSerialnumbers,
        ShipmentItemSerialnumberFactory $shipmentItemSerialnumberFactory,
        SaveMultipleShipmentItemSerialnumbers $saveMultipleShipmentItemSerialnumbers,
        CreditmemoItemSerialnumberFactory $creditmemoItemSerialnumberFactory,
        SaveMultipleCreditmemoItemSerialnumbers $saveMultipleCreditmemoItemSerialnumbers,
        LogFactory $logFactory,
        SaveMultipleLogEntries $saveMultipleLogEntries
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->serialnumberFactory = $serialnumberFactory;
        $this->serialnumberCollectionFactory = $serialnumberCollectionFactory;
        $this->saveMultipleSerialnumbers = $saveMultipleSerialnumbers;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->orderItemSerialnumberFactory = $orderItemSerialnumberFactory;
        $this->saveMultipleOrderItemSerialnumbers
            = $saveMultipleOrderItemSerialnumbers;
        $this->shipmentItemSerialnumberFactory
            = $shipmentItemSerialnumberFactory;
        $this->saveMultipleShipmentItemSerialnumbers
            = $saveMultipleShipmentItemSerialnumbers;
        $this->creditmemoItemSerialnumberFactory
            = $creditmemoItemSerialnumberFactory;
        $this->saveMultipleCreditmemoItemSerialnumbers
            = $saveMultipleCreditmemoItemSerialnumbers;
        $this->logFactory = $logFactory;
        $this->saveMultipleLogEntries = $saveMultipleLogEntries;
    }

    /**
     * Import serial numbers from serial_codes column in sales_order_item table.
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $columnExists = $this->moduleDataSetup
            ->getConnection()
            ->tableColumnExists('sales_order_item', 'serial_codes');

        if ($columnExists) {
            $collection = $this->getOrderItemCollection();
            $this->createSerialnumbers($collection);
            $this->createOrderItemSerialnumbers($collection);
            $this->createShipmentItemSerialnumbers($collection);
            $this->createCreditmemoItemSerialnumbers($collection);
            $this->createSerialnumberLogs($collection);
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Get order item collection with joined tables.
     *
     * @return OrderItemCollection
     */
    private function getOrderItemCollection()
    {
        $collection = $this->orderItemCollectionFactory->create();
        $collection->addFieldToSelect([
            'item_id',
            'sku',
            'serial_codes',
            'order_id',
            'qty_invoiced',
            'qty_refunded',
            'updated_at'
        ]);
        $collection->getSelect()
            ->joinLeft(
                'sales_shipment_item',
                'main_table.item_id = sales_shipment_item.order_item_id',
                ['entity_id AS shipment_item_id','parent_id AS shipment_id']
            )
            ->joinLeft(
                'sales_shipment',
                'sales_shipment_item.parent_id = sales_shipment.entity_id',
                'updated_at AS shipment_updated_at'
            )
            ->joinLeft(
                'inventory_shipment_source',
                'sales_shipment_item.parent_id = inventory_shipment_source.shipment_id',
                'source_code'
            )
            ->joinLeft(
                'inventory_source_item',
                'inventory_shipment_source.source_code = inventory_source_item.source_code AND main_table.sku = inventory_source_item.sku',
                'source_item_id'
            )
            ->joinLeft(
                'sales_creditmemo_item',
                'main_table.item_id = sales_creditmemo_item.order_item_id',
                ['entity_id AS creditmemo_item_id', 'parent_id AS creditmemo_id']
            )
            ->joinLeft(
                'sales_creditmemo',
                'sales_creditmemo_item.parent_id = sales_creditmemo.entity_id',
                'updated_at AS creditmemo_updated_at'
            )
            ->group('main_table.item_id');
        $collection->addFieldToFilter('serial_codes', ['neq' => '']);

        foreach ($collection as $item) {
            $serialCodes = array_unique(preg_split(
                "/\R/",
                $item->getSerialCodes()
            ));

            $serialCodes = array_map('trim', $serialCodes);
            $serialCodes = array_map('strtoupper', $serialCodes);
            $serialCodes = array_filter($serialCodes, [$this, "isValidSerialnumber"]);

            $item->setSerialCodes($serialCodes);
        }

        return $collection;
    }


    /**
     * Create serial numbers from collection.
     *
     * @param OrderItemCollection $orderItemCollection
     * @return void
     */
    private function createSerialnumbers(OrderItemCollection $orderItemCollection)
    {
        $serialnumbers = [];

        foreach ($orderItemCollection as $item) {

            foreach ($item->getSerialCodes() as $serialCode) {
                $serialnumber = $this->serialnumberFactory->create();
                $serialnumber->setSerialnumber($serialCode);
                $serialnumber->setSourceItemId((int)$item->getSourceItemId());
                if ($item->getCreditmemoItemId() !== null ||
                    $item->getQtyInvoiced() === $item->getQtyRefunded()) {
                    $serialnumber->setIsAvailable(true);
                } else {
                    $serialnumber->setIsAvailable(false);
                }

                $serialnumbers[$serialCode] = $serialnumber;
            }
        }

        $this->saveMultipleSerialnumbers->execute(array_values($serialnumbers));
    }

    /**
     * Attach serial numbers to order items.
     *
     * @param OrderItemCollection $orderItemCollection
     * @return void
     */
    private function createOrderItemSerialnumbers(
        OrderItemCollection $orderItemCollection
    ) {
        $serialnumbers = [];

        foreach ($orderItemCollection as $item) {

            foreach ($item->getSerialCodes() as $serialCode) {
                $serialnumber = $this->orderItemSerialnumberFactory->create();
                $serialnumber->setSerialnumberId(
                    $this->getSerialnumberIdByValue($serialCode)
                );
                $serialnumber->setItemId((int)$item->getItemId());
                $serialnumber->setCreatedAt($item->getUpdatedAt());

                $serialnumbers[] = $serialnumber;
            }
        }

        $this->saveMultipleOrderItemSerialnumbers->execute($serialnumbers);
    }

    /**
     * Attach serial numbers to shipment items.
     *
     * @param OrderItemCollection $orderItemCollection
     * @return void
     */
    private function createShipmentItemSerialnumbers(
        OrderItemCollection $orderItemCollection
    ) {
        $serialnumbers = [];

        foreach ($orderItemCollection as $item) {
            if ($item->getShipmentItemId() === null) {
                continue;
            }

            foreach ($item->getSerialCodes() as $serialCode) {
                $serialnumber = $this->shipmentItemSerialnumberFactory->create();
                $serialnumber->setSerialnumberId(
                    $this->getSerialnumberIdByValue($serialCode)
                );
                $serialnumber->setItemId((int)$item->getShipmentItemId());
                $serialnumber->setCreatedAt($item->getShipmentUpdatedAt());

                $serialnumbers[] = $serialnumber;
            }
        }

        $this->saveMultipleShipmentItemSerialnumbers->execute($serialnumbers);
    }

    /**
     * Attach serial numbers to creditmemo items.
     *
     * @param OrderItemCollection $orderItemCollection
     * @return void
     */
    private function createCreditmemoItemSerialnumbers(
        OrderItemCollection $orderItemCollection
    ) {
        $serialnumbers = [];

        foreach ($orderItemCollection as $item) {
            if ($item->getCreditmemoItemId() === null) {
                continue;
            }

            foreach ($item->getSerialCodes() as $serialCode) {
                $serialnumber = $this->shipmentItemSerialnumberFactory->create();
                $serialnumber->setSerialnumberId(
                    $this->getSerialnumberIdByValue($serialCode)
                );
                $serialnumber->setItemId((int)$item->getCreditmemoItemId());
                $serialnumber->setCreatedAt($item->getCreditmemoUpdatedAt());

                $serialnumbers[] = $serialnumber;
            }
        }

        $this->saveMultipleCreditmemoItemSerialnumbers->execute($serialnumbers);
    }

    /**
     * Create import log entry for each imported serial number.
     *
     * @param OrderItemCollection $orderItemCollection
     * @return void
     */
    private function createSerialnumberLogs(
        OrderItemCollection $orderItemCollection
    ) {
        $logEntries = [];

        foreach ($orderItemCollection as $item) {

            foreach ($item->getSerialCodes() as $serialCode) {
                if (isset($logEntries[$serialCode])) {
                    continue;
                }

                $logItem = $this->logFactory->create();
                $logItem->setSerialnumberId(
                    $this->getSerialnumberIdByValue($serialCode)
                );
                $logItem->setMessage('Imported serial number from Qxs_SerialCode.');

                $logEntries[$serialCode] = $logItem;
            }
        }

        $this->saveMultipleLogEntries->execute(array_values($logEntries));
    }

    /**
     * Get serial number ID by serial number.
     *
     * @param string $serialnumber
     * @return int
     */
    private function getSerialnumberIdByValue($serialnumber): int
    {
        if ($this->valueIdMap === null) {
            $this->valueIdMap = [];
            $collection = $this->serialnumberCollectionFactory
                ->create()
                ->addFieldToSelect(['entity_id', SerialnumberInterface::SERIALNUMBER]);

            foreach ($collection as $item) {
                $this->valueIdMap[$item->getSerialnumber()] = $item->getEntityId();
            }
        }

        $serialnumber = strtoupper($serialnumber);
        $result = $this->valueIdMap[$serialnumber] ?? null;

        if ($result === null) {
            throw new NotFoundException(
                __('Serial number %1 not found.', [$serialnumber])
            );
        }

        return (int)$result;
    }

    /**
     * Check if serial number is valid.
     *
     * @param string $serialnumber
     * @return bool
     */
    private function isValidSerialnumber($serialnumber)
    {
        if ($serialnumber === '' ||
            preg_match('/\s/', $serialnumber)) {
            return false;
        }

        return true;
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
        return [];
    }
}
