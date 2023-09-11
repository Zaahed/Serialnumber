<?php

namespace Zaahed\Serialnumber\Block\Adminhtml\Sales\Order\View\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\View\Element\Text\ListText;

class Serialnumbers extends ListText implements TabInterface
{

    /**
     * @inheritDoc
     */
    public function getTabLabel()
    {
        return 'Serial Numbers';
    }

    /**
     * @inheritDoc
     */
    public function getTabTitle()
    {
        return 'Serial Numbers';
    }

    /**
     * @inheritDoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isHidden()
    {
        return false;
    }
}