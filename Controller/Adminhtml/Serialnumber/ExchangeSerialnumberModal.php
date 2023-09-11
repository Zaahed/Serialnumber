<?php

namespace Zaahed\Serialnumber\Controller\Adminhtml\Serialnumber;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Zaahed\Serialnumber\Action\ReplaceOrderSerialnumber;

class ExchangeSerialnumberModal extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Zaahed_Serialnumber::order_item';

    /**
     * @var ReplaceOrderSerialnumber
     */
    private $replaceOrderSerialnumber;

    /**
     * @param Context $context
     * @param ReplaceOrderSerialnumber $replaceOrderSerialnumber
     */
    public function __construct(Context $context, ReplaceOrderSerialnumber $replaceOrderSerialnumber)
    {
        parent::__construct($context);
        $this->replaceOrderSerialnumber = $replaceOrderSerialnumber;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $error = false;
        $messages = [];

        $data = $this->getRequest()->getParam('serialnumber_exchange');


        try {
            $itemSerialnumberId = $data['item_serialnumber_id'];
            $newSerialnumber = $data['new_serialnumber'];
            $returnToSource = empty($data['return_to_source']) ? null : $data['return_to_source'];
            $this->replaceOrderSerialnumber->execute($itemSerialnumberId, $newSerialnumber, $returnToSource);
        } catch (\Exception $e) {
            $messages[] = __('Something went wrong while saving the serial number.');
            $error = true;
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }
}
