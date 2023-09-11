<?php

namespace Zaahed\Serialnumber\Controller\Adminhtml\Serialnumber;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterfaceFactory;
use Zaahed\Serialnumber\Model\ResourceModel\Serialnumber as SerialnumberResource;

class View extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Zaahed_Serialnumber::admin';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var SerialnumberInterfaceFactory
     */
    private $serialnumberFactory;

    /**
     * @var SerialnumberResource
     */
    private $serialnumberResource;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SerialnumberInterfaceFactory $serialnumberFactory
     * @param SerialnumberResource $serialnumberResource
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SerialnumberInterfaceFactory $serialnumberFactory,
        SerialnumberResource $serialnumberResource,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->serialnumberFactory = $serialnumberFactory;
        $this->serialnumberResource = $serialnumberResource;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $serialnumberId = $this->getRequest()->getParam('id');
        if (!is_numeric($serialnumberId) || $serialnumberId <= 0) {

            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addErrorMessage(
                __('Invalid serial number id. Should be numeric and greater than 0.')
            );
            $resultRedirect->setPath('*/*');
            return $resultRedirect;
        }

        $serialnumber = $this->serialnumberFactory->create();
        $this->serialnumberResource->load($serialnumber, $serialnumberId);
        if ($serialnumber->getId() === null) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addErrorMessage(
                __('Serial number not found.')
            );
            $resultRedirect->setPath('*/*');
            return $resultRedirect;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage
            ->getConfig()
            ->getTitle()
            ->prepend($serialnumber->getSerialnumber());


        return $resultPage;
    }
}
