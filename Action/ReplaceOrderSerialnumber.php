<?php
declare(strict_types=1);

namespace Zaahed\Serialnumber\Action;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;
use Zaahed\Serialnumber\Api\OrderItemSerialnumberRepositoryInterface;
use Zaahed\Serialnumber\Api\SerialnumberRepositoryInterface;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterfaceFactory as SerialnumberFactory;
use Zaahed\Serialnumber\Model\Serialnumber\LogManager;

class ReplaceOrderSerialnumber
{
    const SALES_ORDER_VIEW_PATH = 'sales/order/view';
    const SERIALNUMBER_VIEW_PATH = 'catalog/serialnumber/view';

    /**
     * @var SerialnumberFactory
     */
    private $serialnumberFactory;

    /**
     * @var SerialnumberRepositoryInterface
     */
    private $serialnumberRepository;

    /**
     * @var OrderItemSerialnumberRepositoryInterface
     */
    private $orderItemSerialnumberRepository;

    /**
     * @var LogManager
     */
    private $logManager;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var SetSourceItemForSerialnumber
     */
    private $setSourceItemForSerialnumber;

    /**
     * @param SerialnumberFactory $serialnumberFactory
     * @param SerialnumberRepositoryInterface $serialnumberRepository
     * @param OrderItemSerialnumberRepositoryInterface $orderItemSerialnumberRepository
     * @param LogManager $logManager
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param UrlInterface $urlBuilder
     * @param SetSourceItemForSerialnumber $setSourceItemForSerialnumber
     */
    public function __construct(
        SerialnumberFactory $serialnumberFactory,
        SerialnumberRepositoryInterface $serialnumberRepository,
        OrderItemSerialnumberRepositoryInterface $orderItemSerialnumberRepository,
        LogManager $logManager,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        UrlInterface $urlBuilder,
        SetSourceItemForSerialnumber $setSourceItemForSerialnumber
    ) {
        $this->serialnumberFactory = $serialnumberFactory;
        $this->serialnumberRepository = $serialnumberRepository;
        $this->orderItemSerialnumberRepository
            = $orderItemSerialnumberRepository;
        $this->logManager = $logManager;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->urlBuilder = $urlBuilder;
        $this->setSourceItemForSerialnumber = $setSourceItemForSerialnumber;
    }

    /**
     * Exchange order item serial number.
     *
     * @param int $itemSerialnumberId
     * @param string $newSerialnumber
     * @param string|null $returnToSource
     * @return void
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function execute(
        int $itemSerialnumberId,
        string $newSerialnumber,
        ?string $returnToSource = null
    ) {
        $itemSerialnumber = $this->orderItemSerialnumberRepository->get($itemSerialnumberId);
        $itemId = $itemSerialnumber->getItemId();
        $oldSerialnumberId = $itemSerialnumber->getSerialnumberId();
        $oldSerialnumberObject = $this->serialnumberRepository->getById($oldSerialnumberId);

        try {
            $newSerialnumberObject = $this->serialnumberRepository->get($newSerialnumber);
        } catch (NoSuchEntityException $e) {
            $newSerialnumberObject = $this->createNewSerialnumber(
                $newSerialnumber,
                $oldSerialnumberObject->getSourceItemId()
            );
        }
        $newSerialnumberId = (int)$newSerialnumberObject->getId();
        if ($oldSerialnumberId === $newSerialnumberId) {
            return;
        }

        $itemSerialnumber->setSerialnumberId($newSerialnumberId);
        $this->orderItemSerialnumberRepository->save($itemSerialnumber);

        if ($returnToSource !== null) {
            $this->setSourceItemForSerialnumber->execute(
                (int)$oldSerialnumberObject->getId(),
                $this->getOrderItemSku($itemId),
                $returnToSource
            );
        }
        $oldSerialnumberObject->setIsAvailable(true);
        $this->serialnumberRepository->save($oldSerialnumberObject);

        $this->createLogs($oldSerialnumberObject, $newSerialnumberObject, $itemId);
    }

    /**
     * Create serial number logs.
     *
     * @param SerialnumberInterface $oldSerialnumberObject
     * @param SerialnumberInterface $newSerialnumberObject
     * @param int $itemId
     * @return void
     */
    private function createLogs($oldSerialnumberObject, $newSerialnumberObject, $itemId)
    {
        $oldSerialnumber = $oldSerialnumberObject->getSerialnumber();
        $oldSerialnumberId = $oldSerialnumberObject->getId();
        $newSerialnumber = $newSerialnumberObject->getSerialnumber();
        $newSerialnumberId = $newSerialnumberObject->getId();

        $message = 'Exchanged serial number %1 with %2 for order item %3 in order %4.';
        $messageValues = [
            $this->getSerialnumberHref($oldSerialnumber, $oldSerialnumberId),
            $this->getSerialnumberHref($newSerialnumber, $newSerialnumberId),
            $this->getOrderItemName($itemId),
            $this->getOrderIncrementIdHref($itemId)
        ];

        $this->logManager->addLog($oldSerialnumber, $message, $messageValues);
        $this->logManager->addLog($newSerialnumber, $message, $messageValues);
        $this->logManager->addLog(
            $oldSerialnumber,
            'Set status to available.'
        );
        $this->logManager->save();
    }

    /**
     * Create new serial number.
     *
     * @param string $serialnumber
     * @param int $sourceItemId
     * @return SerialnumberInterface
     */
    private function createNewSerialnumber($serialnumber, $sourceItemId)
    {
        $serialnumberObject = $this->serialnumberFactory->create();
        $serialnumberObject->setSerialnumber($serialnumber);
        $serialnumberObject->setIsAvailable(false);
        $serialnumberObject->setSourceItemId($sourceItemId);
        $this->serialnumberRepository->save($serialnumberObject);

        $this->logManager->addLog(
            $serialnumber,
            'Created serial number from exchange.'
        );

        return $serialnumberObject;
    }

    /**
     * Get item name by item ID.
     *
     * @param int $itemId
     * @return string
     */
    private function getOrderItemName($itemId)
    {
        $item = $this->orderItemRepository->get($itemId);
        return trim($item->getName());
    }

    /**
     * Get item SKU by item ID.
     *
     * @param int $itemId
     * @return string
     */
    private function getOrderItemSku($itemId)
    {
        $item = $this->orderItemRepository->get($itemId);
        return $item->getSku();
    }

    /**
     * Get order increment ID as HTML URL by item ID.
     *
     * @param int $itemId
     * @return string
     */
    private function getOrderIncrementIdHref($itemId)
    {
        $orderId = $this->orderItemRepository->get($itemId)->getOrderId();
        $order = $this->orderRepository->get($orderId);
        $incrementId = (string)$order->getIncrementId();

        $url = $this->urlBuilder->getUrl(
            self::SALES_ORDER_VIEW_PATH,
            ['order_id' => $orderId]
        );

        return sprintf('<a href="%s">%s</a>', $url, $incrementId);
    }

    /**
     * Get URL to serial number with serial number as link text.
     *
     * @param string $serialnumber
     * @param int $serialnumberId
     * @return string
     */
    private function getSerialnumberHref($serialnumber, $serialnumberId)
    {
        $url = $this->urlBuilder->getUrl(
            self::SERIALNUMBER_VIEW_PATH,
            ['id' => $serialnumberId]
        );

        return sprintf('<a href="%s">%s</a>', $url, strtoupper($serialnumber));
    }
}
