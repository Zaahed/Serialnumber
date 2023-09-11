<?php

namespace Zaahed\Serialnumber\Controller\Adminhtml\Serialnumber;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Zaahed\Serialnumber\Action\SetSourceItemForSerialnumber;
use Zaahed\Serialnumber\Api\Data\SerialnumberInterface;

class InlineEdit extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Zaahed_Serialnumber::catalog';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SetSourceItemForSerialnumber
     */
    private $setSourceItemForSerialnumber;

    /**
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param SetSourceItemForSerialnumber $setSourceItemForSerialnumber
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        SetSourceItemForSerialnumber $setSourceItemForSerialnumber
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->setSourceItemForSerialnumber = $setSourceItemForSerialnumber;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $error = false;
        $messages = [];

        $items = $this->getRequest()->getParam('items');

        foreach ($items as $entityId => $item) {
            try {
                $sku = $item['sku'];
                $sourceCode = $item['source_code'];
                $serialnumberData = [
                    SerialnumberInterface::IS_AVAILABLE => $item[SerialnumberInterface::IS_AVAILABLE]
                ];
                $this->productRepository->get($sku);
                $this->setSourceItemForSerialnumber->execute($entityId,
                    $sku,
                    $sourceCode,
                    $serialnumberData);
            } catch (NoSuchEntityException $e) {
                $messages[] = __('No product was found with SKU %1.', $sku);
                $error = true;
            } catch (\Exception $e) {
                $messages[] = __('Something went wrong while saving the serial number.');
                $error = true;
            }
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }
}
