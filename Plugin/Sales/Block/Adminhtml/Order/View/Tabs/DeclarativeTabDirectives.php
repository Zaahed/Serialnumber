<?php

namespace Zaahed\Serialnumber\Plugin\Sales\Block\Adminhtml\Order\View\Tabs;

use Magento\Sales\Block\Adminhtml\Order\View\Tabs;

/**
 * <action method="..."> is deprecated. This plugin allows you to specify
 * the tabs inside the $_data property. Example:
 * <block class="Magento\Sales\Block\Adminhtml\Order\View\Tabs"
 *   <arguments>
 *     <argument name="tabs" xsi:type="array">
 *       <item name="tab_name" xsi:type="string">block_name (or class)</item>
 *     </argument>
 *   </arguments>
 * </block>
 */
class DeclarativeTabDirectives
{
    /**
     * Add tabs that were set in the __construct inside the $data array.
     *
     * @param Tabs $subject
     * @return void
     */
    public function beforeToHtml(Tabs $subject) {
        if ($subject->getData('tabs') === null) {
            return;
        }

        foreach ($subject->getData('tabs') as $tabId => $tab) {
            $subject->addTab($tabId, $tab);
        }
    }
}