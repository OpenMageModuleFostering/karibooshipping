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
 * Class Kariboo_Shipping_Model_Returnlabel
 */
class Kariboo_Shipping_Model_Returnlabel extends Mage_Core_Model_Abstract
{
    /**
     * Initialise the model.
     */
    protected function _construct()
    {
        $this->_init("kariboo_shipping/returnlabel");
    }

    /**
     * Gets label from webservice, saves it and returns the saved id.
     *
     * @param $orderId
     * @return int
     */
    public function generateLabelAndSave($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $returnlabel = Mage::getSingleton('kariboo_shipping/webservice')->getReturnLabel($order);

        //convertstring to pdf and save
        $pdfname = Mage::helper('kariboo_shipping')->generatePdfAndSave($returnlabel->label, 'returnlabel', $returnlabel->Barcode);

        //save labeldata for admin display
        $returnLabelObject = new Kariboo_Shipping_Model_Returnlabel;
        $returnLabelObject
            ->setLabelBarcode($returnlabel->Barcode)
            ->setLabelPdfPath($pdfname . ".pdf")
            ->setOrderId($orderId)
            ->setDateCreated(time())
            ->save();
        return $returnLabelObject->getId();
    }

    /**
     * Sends email with custom kariboo email and attached the pdf
     *
     * @param $order
     * @param $returnId
     * @return $this
     */
    public function sendEmail($returnId)
    {
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $returnLabel = Mage::getModel('kariboo_shipping/returnlabel')->load($returnId);
        $order = Mage::getModel('sales/order')->load($returnLabel->getOrderId());
        $billingAddress = $order->getBillingAddress();
        $pdf_attachment = $returnLabel->getLabelPdfPath();
        $templateVars = array('returnlabel' => $returnLabel, 'order' => $order, 'store' => Mage::app()->getStore($order->getStoreId()));
        $transactionalEmail = Mage::getModel('core/email_template')
            ->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStoreId()));
        if (!empty($pdf_attachment) && file_exists(Mage::getBaseDir('media') . "/kariboo/returnlabel/" . $pdf_attachment)) {
            $transactionalEmail->getMail()
                ->createAttachment(
                    file_get_contents(Mage::getBaseDir('media') . "/kariboo/returnlabel/" . $pdf_attachment),
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    basename($pdf_attachment)
                );
        }
        $transactionalEmail->sendTransactional('kariboo_returnlabel_email_template',
                array('name' => Mage::getStoreConfig('trans_email/ident_support/name'),
                    'email' => Mage::getStoreConfig('trans_email/ident_support/email')),
                $billingAddress->getEmail(),
                $billingAddress->getFirstname() . " " . $billingAddress->getLastname(),
                $templateVars);
        $translate->setTranslateInline(true);
        return $billingAddress->getEmail();
    }
}