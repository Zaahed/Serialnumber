<?php

namespace Zaahed\Serialnumber\Plugin\Sales\Model\Order\CreditmemoFactory;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Zaahed\Serialnumber\Model\Creditmemo\Item\SerialnumberFactory;

class SetSerialnumbersOnItems
{
    /**
     * @var SerialnumberFactory
     */
    private $serialnumberFactory;

    /**
     * @param SerialnumberFactory $serialnumberFactory
     */
    public function __construct(SerialnumberFactory $serialnumberFactory)
    {
        $this->serialnumberFactory = $serialnumberFactory;
    }

    /**
     * Set serial numbers on credit memo items.
     *
     * @param CreditmemoFactory $subject
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     * @return Creditmemo
     */
    public function afterCreateByOrder(
        CreditmemoFactory $subject,
        Creditmemo $result,
        \Magento\Sales\Model\Order $order,
        array $data = []
    ) {
        $this->setSerialnumbersOnItems($result, $data);
        return $result;
    }

    /**
     * Set serial numbers on credit memo items.
     *
     * @param CreditmemoFactory $subject
     * @param Creditmemo $result
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param array $data
     * @return Creditmemo
     */
    public function afterCreateByInvoice(
        CreditmemoFactory $subject,
        Creditmemo $result,
        \Magento\Sales\Model\Order\Invoice $invoice,
        array $data = []
    ) {
        $this->setSerialnumbersOnItems($result, $data);
        return $result;
    }

    /**
     * Set serial numbers on credit memo items.
     *
     * @param Creditmemo $creditmemo
     * @param array $data
     * @return void
     */
    private function setSerialnumbersOnItems(Creditmemo $creditmemo, array $data)
    {
        foreach ($creditmemo->getItems() as $item) {
            $orderItemId = $item->getOrderItemId();
            if (!isset($data['items'][$orderItemId]['serialnumbers'])) {
                continue;
            }

            $serialnumbers = array_map(function($serialnumber) {
                return $this->serialnumberFactory
                    ->create()
                    ->setSerialnumberId($serialnumber);
            }, $data['items'][$orderItemId]['serialnumbers']);

            $item->getExtensionAttributes()->setSerialnumbers($serialnumbers);
        }
    }
}