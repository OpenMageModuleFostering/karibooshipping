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
 * Class Kariboo_Shipping_Model_Resource_Returnlabel
 */
class Kariboo_Shipping_Model_Resource_Returnlabel extends Mage_Core_Model_Mysql4_Abstract{
    /**
     * Sets model primary key.
     */
    protected function _construct()
    {
        $this->_init("kariboo_shipping/returnlabel", "label_id");
    }
}