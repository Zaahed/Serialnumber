<?php

namespace Zaahed\Serialnumber\Controller\Adminhtml\Serialnumber;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Zaahed\Serialnumber\Model\Export\ConvertToPdfLabels;

class GridToPdfLabels extends Action implements HttpGetActionInterface
{
    /**
     * @var ConvertToPdfLabels
     */
    private $converter;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @param Context $context
     * @param ConvertToPdfLabels $converter
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        ConvertToPdfLabels $converter,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->converter = $converter;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        return $this->fileFactory->create('labels.pdf', $this->converter->getPdfFile(), 'var');
    }
}