<?php

namespace Zaahed\Serialnumber\Plugin\Sales\Model\ResourceModel\Order\Creditmemo\Item;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item as ItemResource;
use Zaahed\Serialnumber\Model\ResourceModel\Creditmemo\Item\Serialnumber\Collection;
use Zaahed\Serialnumber\Model\ResourceModel\Creditmemo\Item\Serialnumber\CollectionFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Creditmemo\Item\Serialnumber\SaveMultiple;
use Zaahed\Serialnumber\Model\Serialnumber\LogManager;

class SerialnumbersAttribute
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SaveMultiple
     */
    private $saveMultiple;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LogManager
     */
    private $logManager;

    /**
     * @param CollectionFactory $collectionFactory
     * @param SaveMultiple $saveMultiple
     * @param UrlInterface $urlBuilder
     * @param LogManager $logManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        SaveMultiple $saveMultiple,
        UrlInterface $urlBuilder,
        LogManager $logManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->saveMultiple = $saveMultiple;
        $this->urlBuilder = $urlBuilder;
        $this->logManager = $logManager;
    }

    /**
     * Set serial numbers extension attribute on the object after loading.
     *
     * @param ItemResource $subject
     * @param ItemResource $result
     * @param AbstractModel $object
     * @return ItemResource
     */
    public function afterLoad(ItemResource $subject, ItemResource $result, AbstractModel $object)
    {
        if ($object->getEntityId() === null) {
            return $result;
        }

        $serialnumbers = $this->getCollection($object->getEntityId())->getItems();
        /** @var CreditmemoItemInterface $object */
        $object->getExtensionAttributes()->setSerialnumbers($serialnumbers);

        return $result;
    }

    /**
     * Save serial numbers after saving item.
     *
     * @param ItemResource $subject
     * @param ItemResource $result
     * @param AbstractModel $object
     * @return ItemResource
     */
    public function afterSave(ItemResource $subject, ItemResource $result, AbstractModel $object)
    {
        $serialnumberIds = [];

        /** @var CreditmemoItemInterface $object */
        if ($object->getEntityId() === null ||
            $object->getExtensionAttributes()->getSerialnumbers() === null) {
            return $result;
        }

        $alreadySavedSerialnumberIds = $this
            ->getCollection($object->getEntityId())
            ->getColumnValues('serialnumber_id');

        $serialnumbers = array_filter($object->getExtensionAttributes()->getSerialnumbers(),
            function($serialnumber) use ($alreadySavedSerialnumberIds) {
                return !in_array(
                    $serialnumber->getSerialnumberId(),
                    $alreadySavedSerialnumberIds
                );
            }
        );

        foreach ($serialnumbers as $serialnumber) {
            if ($serialnumber->getItemId() !== $object->getEntityId()) {
                $serialnumber->setItemId($object->getEntityId());
            }

            $serialnumberIds[] = $serialnumber->getSerialnumberId();
        }

        $this->saveMultiple->execute($serialnumbers);

        $creditmemoUrl = $this->getCreditmemoHtmlUrl($object->getParentId(), $object->getName());
        foreach ($serialnumberIds as $serialnumberId) {
            $this->logManager->addLogBySerialnumberId(
                $serialnumberId,
                'Credited serial number with credit memo item %1.',
                [$creditmemoUrl]
            );
        }
        $this->logManager->save();

        return $result;
    }

    /**
     * Get credit memo item serial numbers collection.
     *
     * @param int $itemId
     * @return Collection
     */
    private function getCollection($itemId)
    {
        return $this->collectionFactory
            ->create()
            ->addFilter('item_id', $itemId);
    }

    /**
     * Get an 'a' tag with the href attribute set to the admin order url.
     *
     * @param int $creditmemoId
     * @param string $urlText
     * @return string
     */
    private function getCreditmemoHtmlUrl($creditmemoId, $urlText)
    {
        $url = $this->urlBuilder->getUrl(
            'sales/order_creditmemo/view',
            ['creditmemo_id' => $creditmemoId]
        );

        return sprintf('<a href="%s">%s</a>', $url, $urlText);
    }
}