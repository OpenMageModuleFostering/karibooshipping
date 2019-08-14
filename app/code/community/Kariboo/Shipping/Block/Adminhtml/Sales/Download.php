<?php
/**
 * Created by PHPro
 *
 * @package      Kariboo
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */

class Kariboo_Shipping_Block_Adminhtml_Sales_Download extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Sets blockgroup for our Kariboo download page.
     */
    public function __construct()
    {
        $this->_blockGroup = 'kariboo_shipping';
        $this->_controller = 'adminhtml_sales_download';
        $this->_headerText = Mage::helper('kariboo_shipping')->__('Kariboo! Downloads');
        parent::__construct();
        $this->_removeButton('add');
    }
}