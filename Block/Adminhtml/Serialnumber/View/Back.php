<?php

namespace Zaahed\Serialnumber\Block\Adminhtml\Serialnumber\View;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Back implements ButtonProviderInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'class' => 'back',
            'sort_order' => 10,
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
        ];
    }

    /**
     * Get url for back button.
     *
     * @return string
     */
    private function getBackUrl()
    {
        return $this->urlBuilder->getUrl('*/*');
    }
}