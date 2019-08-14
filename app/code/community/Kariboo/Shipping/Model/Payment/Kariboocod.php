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
 * Class Kariboo_Shipping_Model_Method_Kariboocod
 */
class Kariboo_Shipping_Model_Payment_Kariboocod extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code  = 'kariboocod';

    /**
     *
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $isActive = (bool)(int)$this->getConfigData('active', $quote ? $quote->getStoreId() : null);
        $shippingaddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
        $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
        $grandtotal = round($totals["grand_total"]->getValue());

        $karibooShippingMethodSelected = (int)(bool) ($shippingaddress->getShippingMethod() == "kariboo_kariboo");
        $karibooSpotAllowsCod = (int)(bool) ($shippingaddress->getCod() === "true" ||  $shippingaddress->getCod() === null);
        $grandTotalSmallerOrEquals499 = (int)(bool) ($grandtotal <= 499);

        // Shipping method is kariboo_kariboo
        // Payment method is active
        // The spot must support COD
        // The grandtotal must be lower or equal to 499
        if($karibooShippingMethodSelected && $isActive && $karibooSpotAllowsCod && $grandTotalSmallerOrEquals499){
            return true;
        }

        Mage::helper('kariboo_shipping')->log('Not showing Kariboo! COD... karibooselected=' .
                                              $karibooShippingMethodSelected .
                                              ', karibooSpotAllowsCod=' . $karibooSpotAllowsCod .
                                              ', grandTotalSmallerOrEquals499=' . $grandTotalSmallerOrEquals499, Zend_Log::INFO);

        return false;
    }
}
