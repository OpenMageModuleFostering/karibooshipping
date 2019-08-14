<?php
/**
 * Created by PHPro
 *
 * @package      Kariboo
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */
class Kariboo_Shipping_Model_Shipping_Geocode
{
    protected $address_line;
    /**
     * @var Kariboo_Shipping_Helper_Data
     */
    protected $success = false;
    protected $xml;

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this
     */
    public function setAddress(Mage_Sales_Model_Quote_Address $address){
        $addressToInsert = $address->getStreet(1) . " ";
        if ($address->getStreet(2)) {
            $addressToInsert .= $address->getStreet(2) . " ";
        }
        $addressToInsert .= $address->getPostcode() . " " . $address->getCity() . " " . $address->getCountry();

        $this->address_line = $addressToInsert;
        return $this;
    }

    /**
     * Make the call to google geocode
     * @return $this
     */
    public function makeCall()
    {
        $key = Mage::getStoreConfig("carriers/kariboo/google_maps_api");
        $url = 'https://maps.googleapis.com/maps/api/geocode/xml?address=' . urlencode($this->address_line) . '&key='.$key;
        try{
            $xml = simplexml_load_file($url);
            switch($xml->status){
                case "OK":
                    $this->success = true;
                    $this->xml = $xml;
                    Mage::helper('kariboo_shipping')->log("Geocode: OK ".$this->address_line." to xml" ,Zend_Log::DEBUG);
                    return $this;
                    break;
                case "ZERO_RESULTS":
                    Mage::helper('kariboo_shipping')->log("Geocode: no results found for ".$this->address_line,Zend_Log::DEBUG);
                    return false;
                    break;
                case "OVER_QUERY_LIMIT":
                    Mage::helper('kariboo_shipping')->log("Geocode: Over Query Limit. check your api console",Zend_Log::WARN);
                    return false;
                    break;
                case "REQUEST_DENIED":
                    Mage::helper('kariboo_shipping')->log("Geocode: Request denied",Zend_Log::WARN);
                    return false;
                    break;
                case "INVALID_REQUEST":
                    Mage::helper('kariboo_shipping')->log("Geocode: invalid request , address missing?",Zend_Log::WARN);
                    return false;
                    break;
                case "UNKNOWN_ERROR":
                    Mage::helper('kariboo_shipping')->log("Geocode: unknown Error",Zend_Log::WARN);
                    return false;
                    break;
                default:
                    Mage::helper('kariboo_shipping')->log("Geocode: unknown Status",Zend_Log::WARN);
                    return false;
                    break;
            }
        }catch (Exception $e){
            Mage::helper('kariboo_shipping')->log("Geocode: ". $e->getMessage() ,Zend_Log::ERR);
            return false;
        }
    }

    /**
     * Get the Latitude of this object
     * @return string
     */
    public function getLat()
    {
        if($this->success){
            return (string)$this->xml->result->geometry->location->lat;
        }
        return false;
    }
    /**
     * Get the Longitude of this object
     * @return string
     */
    public function getLng()
    {
        if($this->success){
            return (string)$this->xml->result->geometry->location->lng;
        }
        return false;
    }
}