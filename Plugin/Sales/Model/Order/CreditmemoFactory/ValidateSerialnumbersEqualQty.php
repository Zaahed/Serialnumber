<?php

namespace Zaahed\Serialnumber\Plugin\Sales\Model\Order\CreditmemoFactory;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Zaahed\Serialnumber\Action\IsSerialnumbersDisabledForOrderItem;

class ValidateSerialnumbersEqualQty
{
    /**
     * @var OrderItemRepositoryInterface
     */
    private $itemRepository;

    /**
     * @var IsSerialnumbersDisabledForOrderItem
     */
    private $isSerialnumbersDisabledForOrderItem;

    /**
     * @param OrderItemRepositoryInterface $itemRepository
     * @param IsSerialnumbersDisabledForOrderItem $isSerialnumbersDisabledForOrderItem
     */
    public function __construct(
        OrderItemRepositoryInterface $itemRepository,
        IsSerialnumbersDisabledForOrderItem $isSerialnumbersDisabledForOrderItem
    ) {
        $this->itemRepository = $itemRepository;
        $this->isSerialnumbersDisabledForOrderItem
            = $isSerialnumbersDisabledForOrderItem;
    }

    /**
     * @param CreditmemoFactory $subject
     * @param Order $order
     * @param array $data
     * @return void
     */
    public function beforeCreateByOrder(
        CreditmemoFactory $subject,
        \Magento\Sales\Model\Order $order,
        array $data = []
    ) {
        $this->validateQty($data);
    }

    /**
     * @param CreditmemoFactory $subject
     * @param Order\Invoice $invoice
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function beforeCreateByInvoice(
        CreditmemoFactory $subject,
        \Magento\Sales\Model\Order\Invoice $invoice,
        array $data
    ) {
        $this->validateQty($data);
    }

    /**
     * Check if the number of serial numbers provided equals the item qty.
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    private function validateQty(array $data)
    {
        if (!isset($data['items'])) {
            return;
        }

        foreach ($data['items'] as $itemId => $itemData) {
            $item = $this->itemRepository->get($itemId);
            $serialnumberCount =
                isset($itemData['serialnumbers']) && is_array($itemData['serialnumbers'])
                ? count($itemData['serialnumbers'])
                : 0;

            if (empty($item->getExtensionAttributes()->getSerialnumbers()) ||
                $this->isSerialnumbersDisabledForOrderItem->execute($itemId) ||
                $serialnumberCount === (int)$itemData['qty']) {
                continue;
            }

            throw new LocalizedException(__(
                'The number of serial numbers provided for %1 does not match the item quantity.',
                $item->getName()
            ));
        }
    }
}