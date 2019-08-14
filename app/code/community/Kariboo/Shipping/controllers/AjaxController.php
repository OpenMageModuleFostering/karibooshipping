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
 * Class Kariboo_Shipping_AjaxController
 */
class Kariboo_Shipping_AjaxController extends Mage_Core_Controller_Front_Action {

    /**
    * Undefined method
    */
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Get spotswindow
     */
    public function getwindowAction(){
        $isAjax = Mage::app()->getRequest()->isAjax();
        if ($isAjax) {
            //make call and check if it returns an error.
            $wsCall = Mage::helper("kariboo_shipping")->getKaribooSpots();
            $error = array();
            if(gettype($wsCall) != "array"){
                try{
                    $payloadShops = $wsCall->PlugGetSpotsAroundMe01Result->PickUpPoints->PickUpPoint;
                }catch (Exception $e){
                    Mage::helper("kariboo_shipping")->log("Webservice: not expected result returned :" . $e->getMessage() ,Zend_Log::WARN);
                    $payloadShops ="";
                    $error[] = Mage::helper('kariboo_shipping')->__("Sorry, there was a problem contacting Kariboo! , please contact the store owner for support.");
                }

            }else{
                $error[] = $wsCall[0];
                $payloadShops = "";
            }

            $payloadFull = array("error" => $error,"shops" => $payloadShops, "days" => Mage::helper('kariboo_shipping')->getJsDaysArray());
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($payloadFull));
        }
    }
}
