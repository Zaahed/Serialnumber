<?php

namespace Zaahed\Serialnumber\Plugin\Sales\Model\ResourceModel\Order\Shipment\Item;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Item;
use Zaahed\Serialnumber\Model\Serialnumber\LogManager;
use Zaahed\Serialnumber\Model\Shipment\Item\SerialnumberFactory;
use Zaahed\Serialnumber\Registry\SerialnumbersToShip;

class SetSerialnumbersFromRegistry
{
    /**
     * @var SerialnumbersToShip
     */
    private $serialnumbersToShip;

    /**
     * @var SerialnumberFactory
     */
    private $serialnumberFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LogManager
     */
    private $logManager;

    /**
     * @param SerialnumbersToShip $serialnumbersToShip
     * @param SerialnumberFactory $serialnumberFactory
     * @param UrlInterface $urlBuilder
     * @param LogManager $logManager
     */
    public function __construct(
        SerialnumbersToShip $serialnumbersToShip,
        SerialnumberFactory $serialnumberFactory,
        UrlInterface $urlBuilder,
        LogManager $logManager
    ) {
        $this->serialnumbersToShip = $serialnumbersToShip;
        $this->serialnumberFactory = $serialnumberFactory;
        $this->urlBuilder = $urlBuilder;
        $this->logManager = $logManager;
    }

    /**
     * Set serial numbers from registry on shipment item.
     *
     * @param Item $subject
     * @param AbstractModel $object
     * @return void
     */
    public function beforeSave(Item $subject, AbstractModel $object)
    {
        /** @var ShipmentItemInterface $object */
        $serialnumbers = $this->serialnumbersToShip->get($object->getOrderItemId());

        $serialnumbers = array_map(function($serialnumberId) {
            return $this->serialnumberFactory
                ->create()
                ->setSerialnumberId($serialnumberId);
        }, $serialnumbers);
        $object->getExtensionAttributes()->setSerialnumbers($serialnumbers);
    }

    /**
     * Log shipped serial numbers.
     *
     * @param Item $subject
     * @param Item $result
     * @param AbstractModel $object
     * @return Item
     */
    public function afterSave(Item $subject, Item $result, AbstractModel $object)
    {
        /** @var ShipmentItemInterface $object */
        $serialnumbersToShip = $this->serialnumbersToShip->get($object->getOrderItemId());
        $adminShipmentUrlHtml = $this->getAdminShipmentUrlHtml($object, $object->getName());
        foreach ($serialnumbersToShip as $serialnumberId) {
            $this->logManager->addLogBySerialnumberId(
                $serialnumberId,
                'Shipped serial number with shipment item %1.',
                [$adminShipmentUrlHtml]
            );
        }
        $this->logManager->save();

        return $result;
    }

    /**
     *
     * Get an 'a' html tag with the href attribute set to the admin shipment url.
     *
     * @param ShipmentItemInterface $item
     * @param $urlText
     * @return string
     */
    private function getAdminShipmentUrlHtml($item, $urlText)
    {
        $url = $this->urlBuilder->getUrl('sales/shipment/view',
            ['shipment_id' => $item->getParentId()]);

        return sprintf('<a href="%s">%s</a>', $url, $urlText);

    }
}