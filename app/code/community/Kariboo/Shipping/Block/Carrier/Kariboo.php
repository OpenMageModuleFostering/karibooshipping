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
 * Class Kariboo_Shipping_Block_Carrier_Kariboo
 */
class Kariboo_Shipping_Block_Carrier_Kariboo extends Mage_Core_Block_Template
{
    public function _construct()
    {

    }

    public function getResult(){
        $customerData = Mage::getSingleton('customer/session')->getCustomer();
        if($customerData->getKariboo_shopid() != ""){
            $oldSpot = Mage::getSingleton('kariboo_shipping/webservice')->getSpot($customerData->getKariboo_shopid());
            if($oldSpot->Active != false){
                return $oldSpot->PickUpPoint;
            }
        }
        return null;
    }
}