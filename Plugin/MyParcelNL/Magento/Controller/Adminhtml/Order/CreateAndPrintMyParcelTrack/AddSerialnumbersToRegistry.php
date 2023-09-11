<?php

namespace Zaahed\Serialnumber\Plugin\MyParcelNL\Magento\Controller\Adminhtml\Order\CreateAndPrintMyParcelTrack;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Zaahed\Serialnumber\Action\GetShippableSerialnumbersByOrderItemId;
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
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetShippableSerialnumbersByOrderItemId
     */
    private $getShippableSerialnumbersByOrderItemId;

    /**
     * @param RequestInterface $request
     * @param SerialnumbersToShip $serialnumbersToShip
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetShippableSerialnumbersByOrderItemId $getShippableSerialnumbersByOrderItemId
     */
    public function __construct(
        RequestInterface $request,
        SerialnumbersToShip $serialnumbersToShip,
        OrderItemRepositoryInterface $orderItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetShippableSerialnumbersByOrderItemId $getShippableSerialnumbersByOrderItemId
    ) {
        $this->request = $request;
        $this->serialnumbersToShip = $serialnumbersToShip;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getShippableSerialnumbersByOrderItemId
            = $getShippableSerialnumbersByOrderItemId;
    }

    public function beforeExecute()
    {
        if ($this->request->getParam('selected_ids')) {
            $orderIds = explode(',', $this->request->getParam('selected_ids'));
        } else {
            $orderIds = $this->request->getParam('selected');
        }

        if (empty($orderIds)) {
            return;
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter(OrderItemInterface::ORDER_ID, $orderIds, 'in')
            ->create();

        $items = $this->orderItemRepository->getList($criteria)->getItems();
        foreach ($items as $item) {
            $itemId = $item->getItemId();
            $serialnumberIds = array_keys(
                $this->getShippableSerialnumbersByOrderItemId->execute($itemId)
            );
            $this->serialnumbersToShip->set($itemId, $serialnumberIds);
        }
    }
}