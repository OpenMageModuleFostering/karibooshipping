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
 * Class Kariboo_Shipping_Model_Webservice
 */
class Kariboo_Shipping_Model_Webservice extends Mage_Core_Model_Abstract
{

    protected $soap_connection;
    /**
     * @var Kariboo_Shipping_Helper_Data
     */
    protected $_helper;
    protected $connected = false;
    private $password;

    /**
     * Casual construct
     */
    public function _construct()
    {
        $this->_helper = Mage::helper('kariboo_shipping');
        $this->init();
    }

    /**
     * Start initialisation and setup connection
     * @return bool
     */
    protected function init()
    {
        $this->password = Mage::helper('core')->decrypt(Mage::getStoreConfig("shipping/kariboo/authorization_code"));
        $webserviceUrl = Mage::getStoreConfig("shipping/kariboo/webservice_url");

        try {
            $client = new SoapClient($webserviceUrl, array('trace' => TRUE, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS));

            $this->_helper->log('Connecting webservice succeeded', Zend_Log::INFO);
            //$this->_helper->log($result, Zend_Log::DEBUG);
        } catch (SoapFault $soapE) {
            $this->_helper->log('Webservice Login failed:', Zend_Log::ERR);
            $this->_helper->log($soapE->getMessage(), Zend_Log::ERR);
            Mage::getSingleton('adminhtml/session')->addError('A problem occurred with the kariboo webservice, please contact the store owner.');
            return false;
        } catch (Exception $e) {
            $this->_helper->log($e->getMessage(), Zend_Log::ERR);
            return false;
        }

        $this->soap_connection = $client;
        $this->connected = true;
        return true;
    }

    /**
     * Check the connection of the soap client
     * @return bool
     */
    public function checkConnection()
    {
        return $this->connected;
    }


    /**
     * get Kariboo spots
     * @param $lat
     * @param $lng
     * @param string $postalCode
     * @param bool $openOnSunday
     * @param bool $openAfter16
     * @return array
     */
    public function getKaribooSpots($lat, $lng, $postalCode = "", $openOnSunday = "", $openAfter16 = "")
    {
        if ($postalCode != "") {
            $lat = "";
            $lng = "";
        }
        $parameters = array(
            "aroundMe" => array(
                'Longitude' => $lng,
                'Latitude' => $lat,
                'AuthorizationCode' => $this->password,
                'OpenOnSunday' => $openOnSunday,
                'OpenAfter1600' => $openAfter16,
                'PostCode' => $postalCode
            )
        );

        $result = $this->_webserviceCall('PlugGetSpotsAroundMe01', $parameters);
        return $result;
    }

    /**
     * Check if the spot still exists
     * @param string $spotID
     * @return object
     */
    public function getSpot($spotID)
    {
        $parameters = array(
            'shopId' => $spotID,
            'authorizationCode' => $this->password,
        );

        $result = $this->_webserviceCall('PlugGetSpotActive01', $parameters);
        return $result->PlugGetSpotActive01Result;
    }

    /**
     * Get the tracking result and save it
     * @param mixed $trackingNumber
     * @return bool
     */
    public function getTracking($trackingNumber, $objectsToUpdate = null)
    {
        $parameters = array(
            'authorizationCode' => $this->password,
            'TrackingBarcodes' => array("TrackingBarcode" => $trackingNumber)
        );

        $result = $this->_webserviceCall('PlugGetTrackingData01', $parameters);

        //return a boolean if the result failed
        if (!$result) {
            return false;
        }

        //loop trough results and save them
        $transactionModel = Mage::getModel('core/resource_transaction');
        $trackings = (array)$result->PlugGetTrackingData01Result->Trackings;
        if (is_array($trackings)) {
            $trackings = $trackings["Tracking"];
        }
        $size = count($trackings);
        $objectsUpdated = array();
        foreach ($trackings as $tracking) {

            $historyTracking = $tracking->HistoryTrackings->HistoryTracking;
            if (isset($historyTracking[0]->StatusText) && isset($historyTracking[0]->StatusCode)) {
                $objectToUpdate = $objectsToUpdate[$tracking->Barcode];
                $objectsUpdated[] = $tracking->Barcode;
                $objectToUpdate->setKaribooText($historyTracking[0]->StatusText);
                $objectToUpdate->setKaribooStatus($historyTracking[0]->StatusCode);
                $transactionModel->addObject($objectToUpdate);
            }
        }
        $transactionModel->save();
        unset($transactionModel);
        if ($size == 1 && isset($historyTracking[0]->StatusText)) {
            return $historyTracking[0]->StatusText;
        }
        Mage::helper('kariboo_shipping')->log('Updated T&T status for the following barcodes:' . implode(', ', $objectsUpdated), Zend_Log::INFO);
        return true;
    }

    public function getReturnLabel(Mage_Sales_Model_Order $order)
    {
        $billingAddress = $order->getBillingAddress();
        $parameters = array("label" => array(
            'AuthorizationCode' => $this->password,
            'Language' => Mage::helper('kariboo_shipping')->getLanguageFromStore($order->getStoreId()),
            'CustomerName' => $billingAddress->getFirstname() . " " . $billingAddress->getLastname(),
            'CustomerStreet' => $billingAddress->getStreet(1) . " " . $billingAddress->getStreet(2),
            'CustomerCountry' => $billingAddress->getCountry(),
            'CustomerPostalCode' => $billingAddress->getPostcode(),
            'CustomerCity' => $billingAddress->getCity(),
            'Products' => Mage::helper('kariboo_shipping')->processAllShipmentItems($order->getShipmentsCollection())
        ));

        $result = $this->_webserviceCall('PlugGetReturnLabel01', $parameters);
        return $result->PlugGetReturnLabel01Result;
    }

    public function getPickUpLabel(Mage_Sales_Model_Order $order, $shipments, $count)
    {
        $karibooOriginalShippingAddress = json_decode($order->getKaribooOriginalShippingaddress());
        $currencyCode = $order->getOrderCurrencyCode();
        $paymentMethod = $order->getPayment()->getMethodInstance()->getTitle();
        $parameters = array("label" => array(
            'AuthorizationCode' => $this->password,
            'Language' => Mage::helper('kariboo_shipping')->getLanguageFromStore($order->getStoreId()),
            'ShopId' => $order->getKaribooSpotid(),
            'CustomerName' => $karibooOriginalShippingAddress->firstname . " " . $karibooOriginalShippingAddress->lastname,
            'CustomerStreet' => $karibooOriginalShippingAddress->street,
            'CustomerPostalCode' => $karibooOriginalShippingAddress->postcode,
            'CustomerCity' => $karibooOriginalShippingAddress->city,
            'CustomerCountry' => $karibooOriginalShippingAddress->country_id,
            'CustomerEmail' => $order->getCustomerEmail(),
            'CustomerPhone' => $karibooOriginalShippingAddress->telephone,
            'OrderId' => $order->getIncrementId(),
            'NumberLabelToReturn' => (string)$count,
            'Shipments' => Mage::helper('kariboo_shipping')->processShipmentWeight($shipments),
            'COD' => ($paymentMethod == "Kariboo! COD" ? true : false),
            'CODPrice' => '0',
            'Weight' => '0', //obsolete attribute
            'CODCurrency' => strtolower(Mage::app()->getLocale()->currency($currencyCode)->getName()),
            'Products' => Mage::helper('kariboo_shipping')->processAllShipmentItems($shipments)
        ));

        $result = $this->_webserviceCall('PlugGetPickUpLabel01', $parameters);
        return $result->PlugGetPickUpLabel01Result;
    }

    /**
     * Does the webservice call towards kariboo for functions with authentication.
     *
     * @param $method
     * @param $parameters
     * @internal param $webserviceUrl
     * @return mixed
     */
    protected function _webserviceCall($method, $parameters)
    {
        if ($this->connected) {
            try {
                Mage::helper('kariboo_shipping')->log('Starting webservice ' . $method . '...', Zend_Log::INFO);
                Mage::helper('kariboo_shipping')->log(json_encode($parameters), Zend_Log::DEBUG);
                $result = $this->soap_connection->__soapCall($method, array($parameters));

                if ($result->{$method . "Result"}->ErrorCode != "OK") {
                    Mage::helper('kariboo_shipping')->log('Webservice ' . $method . ' failed: ' . $result->{$method . "Result"}->ErrorCode, Zend_Log::ERR);
                    return false;
                }

                Mage::helper('kariboo_shipping')->log('Webservice ' . $method . ' succeeded', Zend_Log::INFO);
                if (($method == "PlugGetPickUpLabel01" || $method == "PlugGetReturnLabel01") && Mage::getStoreConfig('shipping/kariboo/log_level') == 7) {
                    $logTruncated = $this->_truncateLog($method, $result);
                    Mage::helper('kariboo_shipping')->log($logTruncated, Zend_Log::DEBUG);
                } else {
                    Mage::helper('kariboo_shipping')->log($result, Zend_Log::DEBUG);
                }
                return $result;

            } catch (SoapFault $soapE) {
                Mage::helper('kariboo_shipping')->log('Webservice ' . $method . ' failed:', Zend_Log::ERR);
                Mage::helper('kariboo_shipping')->log($soapE->getMessage(), Zend_Log::ERR);
                return false;
            } catch (Exception $e) {
                Mage::helper('kariboo_shipping')->log($e->getMessage(), Zend_Log::ERR);
                Mage::getSingleton('adminhtml/session')->addError('Something went wrong with the webservice, please check the log files.');
                return false;
            }

        } else {
            Mage::helper('kariboo_shipping')->log("Webservice not initialised.", Zend_Log::ERR);
            return false;
        }

    }

    protected function _truncateLog($method, $result)
    {
        $arrayToLog = array();
        if ($method == "PlugGetPickUpLabel01") {
            $arrayToLog['ErrorCode'] = $result->PlugGetPickUpLabel01Result->ErrorCode;
            $counter = 0;
            foreach ($result->PlugGetPickUpLabel01Result->LabelPdfs->LabelPdf as $labelPdf) {
                $arrayToLog['LabelPdf'][$counter]['Barcode'] = $labelPdf->Barcode;
                $arrayToLog['LabelPdf'][$counter]['BarcodeReturn'] = $labelPdf->BarcodeReturn;
                $editedLabel = substr(str_replace(array("\r\n", "\n", "\r"), ' ', $labelPdf->label), 0, 50);
                $arrayToLog['LabelPdf'][$counter]['label'] = $editedLabel;
                $counter++;
            }
        }
        else{
            $arrayToLog['ErrorCode'] = $result->PlugGetReturnLabel01Result->ErrorCode;
            $arrayToLog['Barcode'] = $result->PlugGetReturnLabel01Result->Barcode;
            $editedLabel = substr(str_replace(array("\r\n", "\n", "\r"), ' ', $result->PlugGetReturnLabel01Result->label),0, 50);
            $arrayToLog['label'] = $editedLabel;
        }
        return $arrayToLog;
    }
}