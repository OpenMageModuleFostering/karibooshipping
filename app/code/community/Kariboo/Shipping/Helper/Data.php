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
 * Class Kariboo_Shipping_Helper_Data
 */
class Kariboo_Shipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Logs bugs/info.
     * Zend_Log::DEBUG = 7
     * Zend_Log::ERR = 3
     * Zend_Log::INFO = 6
     *
     * @param $message
     * @param $level
     */
    public function log($message, $level)
    {
        $allowedLogLevel = Mage::getStoreConfig('shipping/kariboo/log_level');
        if ($level <= $allowedLogLevel) {
            Mage::log($message, $level, 'kariboo.log');
        }
    }

    /**
     * Gets all spots from kariboo webservice based on shipping address and selected filter.
     *
     * @return mixed
     */
    public function getKaribooSpots()
    {
        $geocode = Mage::getModel("kariboo_shipping/shipping_geocode")->setAddress(Mage::getModel('checkout/cart')->getQuote()->getShippingAddress())->makeCall();
        if ($geocode) {
            $param_openafter = Mage::app()->getRequest()->getPost("filter_openafter16") == "on" ? 'true' : '';
            $param_opensunday = Mage::app()->getRequest()->getPost("filter_openonsunday") == "on" ? 'true' : '';
            $param_postalcode = Mage::app()->getRequest()->getPost("filter_postalcode");
            return Mage::getSingleton('kariboo_shipping/webservice')->getKaribooSpots($geocode->getLat(), $geocode->getLng(), $param_postalcode, $param_opensunday, $param_openafter);
        } else {
            return array(Mage::helper('kariboo_shipping')->__("Sorry, something went wrong processing your shipping address. Your address was not recognised. Please correct any errors in your shipping address and try again."));
        }
    }

    /**
     *
     * Creates new IO object and inputs base 64 pdf string fetched from webservice.
     *
     * @param $pdfString
     * @param $folder
     * @param $name
     */
    public function generatePdfAndSave($pdfString, $folder, $name)
    {
        $hash = bin2hex(mcrypt_create_iv(5, MCRYPT_DEV_URANDOM));;
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => Mage::getBaseDir('media') . "/kariboo/" . $folder));
        $io->streamOpen($name . "-" . $hash . '.pdf', 'w+');
        $io->streamLock(true);
        $io->streamWrite($pdfString);
        $io->streamUnlock();
        $io->streamClose();
        return $name . "-" . $hash;
    }


    /**
     * @param $shipment
     * @return int
     */
    public function calculateTotalShippingWeight($shipment)
    {
        $weight = 0;
        $shipmentItems = $shipment->getAllItems();
        foreach ($shipmentItems as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            if (!$orderItem->getParentItemId()) {
                $weight = $weight + ($shipmentItem->getWeight() * $shipmentItem->getQty());
            }
        }

        return $weight;
    }

    /**
     * Returns the language based on storeId.
     *
     * @param $storeId
     * @return string language
     */
    public function getLanguageFromStore($storeId)
    {
        $locale = Mage::app()->getStore($storeId)->getConfig('general/locale/code');
        $localeCode = explode('_', $locale);
        return $localeCode[0];
    }


    /**
     * @param $shipments
     * @return mixed
     */
    public function processAllShipmentItems($shipments)
    {
        $productsInfoArray['product'] = array();
        foreach ($shipments as $shipment) {
            $shipmentItems = $shipment->getItemsCollection();
            foreach ($shipmentItems as $shipmentItem) {
                $orderItem = $shipmentItem->getOrderItem();
                if (!$orderItem->getParentItemId()) {
                    $productsInfoArray['product'][] = array('Id' => $shipmentItem->getProductId(), 'Name' => $shipmentItem->getName(), 'Quantity' => $shipmentItem->getQty());
                }
            }
        }
        return $productsInfoArray;
    }


    /**
     * @param $shipments
     * @return int
     */
    public function processShipmentWeight($shipments)
    {
        $weightPerShipmentArray['Shipment'] = array();
        foreach ($shipments as $shipment) {
            if (Mage::getStoreConfig('shipping/kariboo_weight_unit') == "") {
                $weight = $shipment->getTotalWeight() * 100;
            } else {
                $weight = $shipment->getTotalWeight() * Mage::getStoreConfig('shipping/kariboo_weight_unit');
            }
            $weightPerShipmentArray['Shipment'][] = array('Weight' => $weight);
        }

        return $weightPerShipmentArray;
    }

    /**
    * @return array
    */
    public function getJsDaysArray()
    {
        $jsDaysArray = array("LU" => $this->__("Ma"), "MA" => $this->__("Di"), "ME" => $this->__("Wo"), "JE" => $this->__("Do"), "VE" => $this->__("Vr"), "SA" => $this->__("Za"), "DI" => $this->__("Zo"));
        return $jsDaysArray;
    }
}