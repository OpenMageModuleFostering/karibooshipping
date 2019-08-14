<?php
/**
 * Created by PHPro
 *
 * @package      Kariboo
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Kariboo_Shipping_Adminhtml_KaribooconfigController
 */
class Kariboo_Shipping_Adminhtml_KaribooconfigController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Export shipping table rates in csv format
     *
     */
    public function exportKaribooTableratesAction()
    {
        $fileName = 'kariboo_tablerates.csv';
        /** @var $gridBlock Mage_Adminhtml_Block_Shipping_Carrier_Tablerate_Grid */
        $gridBlock = $this->getLayout()->createBlock('kariboo_shipping/adminhtml_shipping_carrier_kariboo_tablerate_grid');
        $website = Mage::app()->getWebsite($this->getRequest()->getParam('website'));
        if ($this->getRequest()->getParam('conditionName')) {
            $conditionName = $this->getRequest()->getParam('conditionName');
        } else {
            $conditionName = $website->getConfig('carriers/kariboo/table_rate_condition');
        }
        $gridBlock->setWebsiteId($website->getId())->setConditionName($conditionName);
        $content = $gridBlock->getCsvFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }
}