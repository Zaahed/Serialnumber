<?php

namespace Zaahed\Serialnumber\Plugin\Shipping\Controller\Adminhtml\Order\Shipment\Save;

use Magento\Framework\App\RequestInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save;
use Zaahed\Serialnumber\Registry\SerialnumbersToShip;

class AddSerialnumbersToRegistry
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SerialnumbersToShip
     */
    private $serialnumbersToShip;

    /**
     * @param RequestInterface $request
     * @param SerialnumbersToShip $serialnumbersToShip
     */
    public function __construct(
        RequestInterface $request,
        SerialnumbersToShip $serialnumbersToShip
    ) {
        $this->request = $request;
        $this->serialnumbersToShip = $serialnumbersToShip;
    }

    public function beforeExecute(Save $subject)
    {
        $data = $this->request->getParam('shipment', []);
        $serialnumbers = $data['serialnumbers'] ?? [];

        foreach ($serialnumbers as $itemId => $serialnumberIds) {
            $this->serialnumbersToShip->set($itemId, $serialnumberIds);
        }
    }
}