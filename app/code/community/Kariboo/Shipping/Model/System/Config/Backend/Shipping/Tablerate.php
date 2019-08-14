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
 * Class Kariboo_Shipping_Model_System_Config_Backend_Shipping_Tablerate
 */
class Kariboo_Shipping_Model_System_Config_Backend_Shipping_Tablerate extends Mage_Core_Model_Config_Data
{
    /**
     * Call the uploadAndImport function from the classic tablerate recourcemodel.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('kariboo_shipping/tablerate')->uploadAndImport($this);
    }
}