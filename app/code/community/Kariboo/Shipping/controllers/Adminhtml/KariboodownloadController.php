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
 * Class Kariboo_Shipping_Adminhtml_KariboodownloadController
 */
class Kariboo_Shipping_Adminhtml_KariboodownloadController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Load indexpage of this controller.
     */
    public function indexAction()
    {
        Mage::getModel('core/session')->setKaribooReturn(0);
        $this->_title($this->__('kariboo'))->_title($this->__('Kariboo! Downloads'));
        $this->loadLayout();
        $this->_setActiveMenu('sales/sales');
        $this->_addContent($this->getLayout()->createBlock('kariboo_shipping/adminhtml_sales_download'));
        $this->renderLayout();
    }

    /**
     * Load the grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('kariboo_shipping/adminhtml_sales_download_grid')->toHtml()
        );
    }
}