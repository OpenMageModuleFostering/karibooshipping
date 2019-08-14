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
 * Class Kariboo_Shipping_Model_Mysql4_Returnlabel_Collection
 */
class Kariboo_Shipping_Model_Resource_Returnlabel_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialises the model, the abstract file will render a collection from it.
     */
    public function _construct()
    {
        $this->_init("kariboo_shipping/returnlabel");
    }
}