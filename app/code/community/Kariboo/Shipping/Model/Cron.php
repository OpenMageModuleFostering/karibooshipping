<?php

class Kariboo_Shipping_Model_Cron
{
    public function reloadTrackingStatus()
    {
        Mage::helper('kariboo_shipping')->log('T&T Cron – START', Zend_Log::INFO);

        $updateArray = array();
        $objectsToUpdate = array();
        $result = Mage::getResourceModel('sales/order_shipment_track_collection')
            ->addAttributeToFilter("carrier_code", "kariboo")
            ->addFieldToFilter("kariboo_status", array(array('neq' => 68), array('neq' => 148)))
            ->addAttributeToSelect("*")
            ->join(array("order" => "sales/order"), " main_table.order_id=order.entity_id", array("store_id" => "store_id"));
        foreach ($result as $value) {
            $updateArray[] = array(
                "barcode" => $value->getNumber(),
                "language" => Mage::helper('kariboo_shipping')->getLanguageFromStore($value->getStoreId())
            );
            $value->unsOrder();
            $objectsToUpdate[$value->getNumber()] = $value;
        }
        unset($result);

        $wscall = Mage::getSingleton("kariboo_shipping/webservice")->getTracking($updateArray, $objectsToUpdate);

        if ($wscall) {
            Mage::helper("kariboo_shipping")->log("Tracking: " . count($updateArray) . " trackingnumbers updated", Zend_Log::DEBUG);
        } else {
            Mage::helper("kariboo_shipping")->log("Tracking: " . count($updateArray) . " trackingnumbers not updated", Zend_Log::WARN);
        }
        
        Mage::helper('kariboo_shipping')->log('T&T Cron - END', Zend_Log::INFO);
    }
}