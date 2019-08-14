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
 * Class Kariboo_Shipping_Model_Shipping_Kariboo
 */
class Kariboo_Shipping_Model_Shipping_Kariboo extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * @var string
     */
    protected $_code = 'kariboo';

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return bool|Mage_Shipping_Model_Rate_Result|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        //If webservice does not work or carrier is not set active, stop initializing.
        $webservice = Mage::getSingleton("kariboo_shipping/webservice");
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        if (!Mage::getStoreConfig('carriers/' . $this->_code . '/active') || !$webservice->checkConnection() || $quote->getData('is_multi_shipping')) {
            return false;
        }

        $method = Mage::getModel('shipping/rate_result_method');
        $result = Mage::getModel('shipping/rate_result');

        if (!$this->getConfigData('rate_type')) {
            $price = $this->getConfigData('flat_rate_price');
            if ($request->getFreeShipping() === true ||
                (Mage::getStoreConfig('carriers/' . $this->_code . '/free_shipping') &&
                    $request->getPackageValue() >= Mage::getStoreConfig('carriers/' . $this->_code . '/free_shipping_from'))) {
                $price = 0;
            }
        } else {
            $freeQty = 0;
            if ($request->getAllItems()) {
                $freePackageValue = 0;
                foreach ($request->getAllItems() as $item) {
                    if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                        continue;
                    }

                    if ($item->getHasChildren() && $item->isShipSeparately()) {
                        foreach ($item->getChildren() as $child) {
                            if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                                $freeShipping = is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0;
                                $freeQty += $item->getQty() * ($child->getQty() - $freeShipping);
                            }
                        }
                    } elseif ($item->getFreeShipping()) {
                        $freeShipping = is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0;
                        $freeQty += $item->getQty() - $freeShipping;
                        $freePackageValue += $item->getBaseRowTotal();
                    }
                }
                $oldValue = $request->getPackageValue();
                $request->setPackageValue($oldValue - $freePackageValue);
            }

            if ($freePackageValue) {
                $request->setPackageValue($request->getPackageValue() - $freePackageValue);
            }

            $conditionName = $this->getConfigData('table_rate_condition');
            $request->setConditionName($conditionName ? $conditionName : $this->_default_condition_name);

            $oldWeight = $request->getPackageWeight();
            $oldQty = $request->getPackageQty();

            $request->setPackageWeight($request->getFreeMethodWeight());
            $request->setPackageQty($oldQty - $freeQty);

            $rate = $this->getRate($request);
            $request->setPackageWeight($oldWeight);
            $request->setPackageQty($oldQty);

            if (!empty($rate) && $rate['price'] >= 0) {
                if ($request->getFreeShipping() === true || ($request->getPackageQty() == $freeQty)) {
                    $price = 0;
                } else {
                    $price = $rate['price'];
                }
            } elseif (empty($rate) && $request->getFreeShipping() === true) {
                $request->setPackageValue($freePackageValue);
                $request->setPackageQty($freeQty);
                $rate = $this->getRate($request);
                if (!empty($rate) && $rate['price'] >= 0) {
                    $price = 0;
                }
            } else {
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage(Mage::helper('kariboo_shipping')->__('This shipping method is currently unavailable. If you would like to ship using this shipping method, please contact us.'));
                $result->append($error);
                return $result;
            }

        }

        $method->setCarrier($this->_code);
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setPrice($price);
        $method->setCost($price);
        $result->append($method);

        return $result;
    }

    /**
     * Add this shipping method to list of allowed methods so Magento can display it.
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array('kariboospots' => $this->getConfigData('name'));
    }

    /**
     * Get tracking result object.
     *
     * @param string $tracking_number
     * @return Mage_Shipping_Model_Tracking_Result $tracking_result
     */
    public function getTrackingInfo($tracking_number)
    {
        $tracking_result = $this->getTracking($tracking_number);

        if ($tracking_result instanceof Mage_Shipping_Model_Tracking_Result) {
            $trackings = $tracking_result->getAllTrackings();
            if (is_array($trackings) && count($trackings) > 0) {
                return $trackings[0];
            }
        }
        return false;
    }

    /**
     * Get tracking Url.
     *
     * @param string $tracking_number
     * @return Mage_Shipping_Model_Tracking_Result
     */
    public function getTracking($tracking_number)
    {
        $collection = Mage::getResourceModel('sales/order_shipment_track_collection')
            ->addFieldToFilter('track_number', $tracking_number)
            ->addAttributeToSelect("*");

        $status = Mage::getSingleton("kariboo_shipping/webservice")->getTracking(
            array(
                "barcode" => $tracking_number,
                "language" => Mage::helper('kariboo_shipping')->getLanguageFromStore(Mage::app()->getStore()->getStoreId())
            ),array($tracking_number => $collection->getFirstItem()));

        if(!$status){
            $returntext = $collection->getFirstItem()->getKaribooStatus();
            $returntext .= "<br />" . Mage::helper('kariboo_shipping')->__("Real-time data is not available at the moment, last status update is from %s", $collection->getFirstItem()->getUpdatedAt());
        }else{
            $returntext = $status;
        }
        if(!$returntext || $returntext == "" || $returntext == "1"){
            $returntext = Mage::helper('kariboo_shipping')->__("Tracking info is not available. Please contact the store owner.");
        }
        $tracking_result = Mage::getModel('shipping/tracking_result');
        $tracking_status = Mage::getModel('shipping/tracking_result_status');
        $tracking_status->setCarrier($this->_code);
        $tracking_status->setCarrierTitle($this->getConfigData('title'));
        $tracking_status->setTracking($tracking_number);
        $tracking_status->addData(
            array(
                'status' => $returntext
            )
        );
        $tracking_result->append($tracking_status);

        return $tracking_result;
    }

    /**
     * Make tracking available for kariboo shippingmethods.
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Make shipping labels not available as we provided our own method.
     *
     * @return bool
     */
    public function isShippingLabelsAvailable()
    {
        return false;
    }

    /**
     * Get the rateobject from our resource model.
     *
     * @param $request
     * @return mixed
     */
    public function getRate($request)
    {
        return Mage::getResourceModel('kariboo_shipping/tablerate')->getRate($request);
    }
}