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
 * Class Kariboo_Shipping_Model_Observer
 */
class Kariboo_Shipping_Model_Observer
{
    /**
     * Sets generate return label button on order detail view in the admin.
     * Sets download kariboo button on shipment order detail.
     *
     * @param $observer
     */
    public function core_block_abstract_to_html_before($observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View && $block->getRequest()->getControllerName() == 'sales_order') {
            $orderId = Mage::app()->getRequest()->getParam('order_id');
            $block->addButton('print_retour_label', array(
                'label' => Mage::helper('kariboo_shipping')->__('Kariboo! Return Label'),
                'onclick' => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/karibooorder/generateReturnLabel/order_id/' . $orderId) . '\')',
                'class' => 'go'
            ));

        }
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View && $block->getRequest()->getControllerName() == "sales_order_shipment") {
            $shipment = Mage::registry('current_shipment');
            $shipmentId = $shipment->getId();
            $order = Mage::getModel('sales/order')->load($shipment->getOrderId());
            if (strpos($order->getShippingMethod(), 'kariboo') !== false) {
                $block->addButton('download_kariboo_label', array(
                    'label' => Mage::helper('kariboo_shipping')->__('Download Kariboo! Label'),
                    'onclick' => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/karibooorder/downloadKaribooLabel/shipment_id/' . $shipmentId) . '\')',
                    'class' => 'scalable save'
                ));
            }
        }
    }

    /**
     * Calculate and set the weight on the shipping to pass it to the webservice after a standard shipment save.
     *
     * @param $observer
     */
    public function sales_order_shipment_save_before($observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        if (!$shipment->hasId() && !$shipment->getTotalWeight()) {
            $weight = Mage::helper('kariboo_shipping')->calculateTotalShippingWeight($shipment);
            $shipment->setTotalWeight($weight);
        }
    }

    /**
     * Observes html load, this will add html to the Kariboo shipping method.
     *
     * @param $observer
     * @return $this
     */
    public function core_block_abstract_to_html_after($observer)
    {
        if ($observer->getBlock() instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Available) {
            $availablerates = $observer->getBlock()->getShippingRates();
            if (array_key_exists("kariboo", $availablerates)) {
                //get HTML
                $html = $observer->getTransport()->getHtml();
                //intercept html and append block
                $html .= Mage::app()->getLayout()->createBlock("kariboo_shipping/carrier_kariboo")->setTemplate("kariboo/shipping/kariboo_checkout_append.phtml")->toHtml();
                //set HTML
                $observer->getTransport()->setHtml($html);
            }
        }
        if ($observer->getBlock() instanceof Mage_Checkout_Block_Onepage_Payment_Methods) {
            if (Mage::getSingleton('checkout/session')->getKaribooReloadProgress()) {
                $html = $observer->getTransport()->getHtml();
                $html .= "<script>checkout.reloadProgressBlock('shipping');</script>";
                $observer->getTransport()->setHtml($html);
                Mage::getSingleton('checkout/session')->unsKaribooReloadProgress();
            }
            return $this;
        }
    }

    /**
     * Observe shipping address and create alternative shipping address in the session. (we select only the necessary data to keep the object small)
     *
     * @param $observer
     * @return $this
     */
    public function controller_action_postdispatch_checkout_onepage_saveAddress($observer)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $shippingAddress = $checkoutSession->getQuote()->getShippingAddress();
        if (((bool)$shippingAddress->getSameAsBilling() && $observer->getEvent()->getName() == "controller_action_postdispatch_checkout_onepage_saveBilling") ||
            $observer->getEvent()->getName() == "controller_action_postdispatch_checkout_onepage_saveShipping"
        ) {
            $karibooOriginalShippingAddress = new Varien_Object();
            $karibooOriginalShippingAddress
                ->setFirstname($shippingAddress->getFirstname())
                ->setLastname($shippingAddress->getLastname())
                ->setCompany($shippingAddress->getCompany())
                ->setStreet($shippingAddress->getStreet(1) . " " . $shippingAddress->getStreet(2))
                ->setCity($shippingAddress->getCity())
                ->setRegion($shippingAddress->getRegion())
                ->setPostcode($shippingAddress->getPostcode())
                ->setCountryId($shippingAddress->getCountryId())
                ->setTelephone($shippingAddress->getTelephone())
                ->setFax($shippingAddress->getFax());
            if($shippingAddress->getAddressId() != "" && $shippingAddress->hasAddressId()){
                $karibooOriginalShippingAddress->setAddressId($shippingAddress->getAddressId());
            }
            $checkoutSession->setKaribooOriginalShippingAddress($karibooOriginalShippingAddress);
        }
        return $this;
    }

    /**
     * Change shipping address if needed, save previous shipping method, add progress bar reload flag.
     *
     * @param $observer
     * @return $this
     */
    public function checkout_controller_onepage_save_shipping_method($observer)
    {
        //init all neseccary data
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();
        $address = $quote->getShippingAddress();

        //get the kariboo data
        $params = Mage::app()->getRequest()->getPost();

        //if all necessary fields are filled in.
        if ($address->getShippingMethod() == "kariboo_kariboo" && $params["kariboo"]["spotid"] && $params["kariboo"]["name"] && $params["kariboo"]["street"]
            && $params["kariboo"]["city"] && $params["kariboo"]["postcode"]
        ) {

            //set the spot as chosen by the customer
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                Mage::getSingleton('customer/session')
                    ->getCustomer()
                    ->setKariboo_shopid($params["kariboo"]["spotid"])
                    ->save();
            }
            //set it in the quote
            Mage::getSingleton('checkout/session')->getQuote()->setKariboo_spotid($params["kariboo"]["spotid"]);

            //now set the current address to the kariboo!-spot and add a flag for progress reload
            $address->unsetAddressId()
                ->setTelephone("00 000 00 00")
                ->setFax('')
                ->setSaveInAddressBook(0)
                ->setFirstname('Kariboo!-spot: ')
                ->setLastname($params["kariboo"]["name"])
                ->setStreet($params["kariboo"]["street"])
                ->setCity($params["kariboo"]["city"])
                ->setPostcode($params["kariboo"]["postcode"])
                ->setCod($params["kariboo"]["cod"])
                ->save();
            $checkoutSession->setKaribooReloadProgress(true);
        } elseif ($checkoutSession->getAlternativeShippingMethod() == "kariboo_kariboo") {

            //if prev shippingmethod was kariboo, change shipping address

            $mergedShippingAddressData = array_merge($address->getData(), $checkoutSession->getKaribooOriginalShippingAddress()->getData());
            $address->setData($mergedShippingAddressData);
            $checkoutSession->setKaribooReloadProgress(true);

        }
        else{

            //reload is not needed

            $checkoutSession->setKaribooReloadProgress(false);
        }

        //set this so we know what the previous method was.
        $checkoutSession->setAlternativeShippingMethod($address->getShippingMethod());
        return $this;
    }

    /**
     * @param $observer
     */
    public function checkout_submit_all_after($observer){
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();
        if($quote->getShippingAddress()->getShippingMethod()){
            $observer->getEvent()->getOrder()
                ->setKaribooOriginalShippingaddress(json_encode(Mage::getSingleton('checkout/session')->getKaribooOriginalShippingAddress()->getData()))
                ->setKariboo_spotid($quote->getKariboo_spotid())
                ->save();
        }

        //remember the spot
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('customer/session')
                ->getCustomer()
                ->setKariboo_shopid($quote->getKariboo_spotid())
                ->save();
        }
    }
}