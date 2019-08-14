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
 * Class Kariboo_Shipping_Adminhtml_KaribooorderController
 */
class Kariboo_Shipping_Adminhtml_KaribooorderController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Load indexpage of this controller.
     */
    public function indexAction()
    {
        Mage::getSingleton('core/session')->setKaribooReturn(0);
        $this->_title($this->__('kariboo'))->_title($this->__('Kariboo! Orders'));
        $this->loadLayout();
        $this->_setActiveMenu('sales/sales');
        $this->_addContent($this->getLayout()->createBlock('kariboo_shipping/adminhtml_sales_order'));
        $this->renderLayout();
    }

    /**
     * Load the grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('kariboo_shipping/adminhtml_sales_order_grid')->toHtml()
        );
    }

    /**
     * Export csvs, this fetches all current gridentries.
     */
    public function exportKaribooOrdersCsvAction()
    {
        $fileName = 'kariboo_orders.csv';
        $grid = $this->getLayout()->createBlock('kariboo_shipping/adminhtml_sales_order_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     * Export excel (xml), this fetches all current gridentries.
     */
    public function exportKaribooOrdersExcelAction()
    {
        $fileName = 'kariboo_orders.xml';
        $grid = $this->getLayout()->createBlock('kariboo_shipping/adminhtml_sales_order_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

    /**
     * Generate the returnlabel pdf, this fetches the label from kariboo webservices.
     * On fail this method will remove all db entries / generated pdfs to prevent outputting wrong entries.
     * Logs all errors in try catch.
     */
    public function generateReturnLabelAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            $returnId = Mage::getModel('kariboo_shipping/returnlabel')->generateLabelAndSave($orderId);
            if ($returnId) {
                $message = Mage::helper('kariboo_shipping')->__("Your return label has been generated and is available under 'Kariboo! Return Labels' in this order.");
                Mage::getSingleton('core/session')->addSuccess($message);
            }
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->_redirect('adminhtml/sales_order/view/order_id/' . $orderId);
        return $this;
    }

    /**
     * Fetches the label and puts it in a download response.
     */
    public function downloadKaribooLabelAction()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
        if ($shipment->getKaribooLabelPath() == "") {
            $message = Mage::helper('kariboo_shipping')->__("No label generated yet - please perform the ‘Generate Label and Complete’ action from the overview.");
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('*/sales_order_shipment/view/shipment_id/' . $shipmentId);
        } else {
            $pdf_Path = Mage::getBaseDir('media') . "/kariboo/orderlabels/" . $shipment->getKaribooLabelPath();
            $this->_prepareDownloadResponse($shipment->getKaribooLabelPath(), file_get_contents($pdf_Path), 'application/pdf');
            $shipment->setKaribooLabelExported(1)->save();
        }
    }

    /**
     * Call this to send an email with the kariboo template.
     * This calls the model to handle emails the magento way.
     * Logs all errors in try catch.
     */
    public function sendEmailAction()
    {
        $returnId = $this->getRequest()->getParam('return_id');
        try {
            $email = Mage::getModel('kariboo_shipping/returnlabel')->sendEmail($returnId);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirectReferer();
            return $this;
        }
        $message = Mage::helper('kariboo_shipping')->__("The email with return label has been sent to %s.", $email);
        Mage::getSingleton('core/session')->addSuccess($message);
        $this->_redirectReferer();
        return $this;
    }

    /**
     * Generates a label and completes the shipment.
     * This is called by the action in the order grid dropdown.
     */
    public function generateAndCompleteAction()
    {
        ini_set('max_execution_time', 120);
        $orderIds = $this->getRequest()->getParam('entity_id');

        //load order collection and process each order
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('entity_id', array('in' => $orderIds));

        //count orders to show how much have been processed in feedback
        $counter = 0;
        $ordercounter = 0;
        try {
            foreach ($orderCollection as $order) {
                try {
                    $result = Mage::getModel('kariboo_shipping/adminhtml_kariboogrid')->generateAndCompleteOrder($order);
                    if ($result != false) {
                        $counter= $counter + $result;
                        $ordercounter++;
                    }
                } catch (Exception $e) {
                    Mage::helper('kariboo_shipping')->log($e->getMessage(), Zend_Log::ERR);
                    $message = Mage::helper('kariboo_shipping')->__("The order with id %s is not ready to be shipped or has already been shipped.", $order->getIncrementId());
                    Mage::getSingleton('core/session')->addNotice($message);
                }
            }
            if ($counter > 0) {
                $message = Mage::helper('kariboo_shipping')->__("%s label(s) have been generated for %s order(s) and statuses have been changed.", $counter, $ordercounter);
                Mage::getSingleton('core/session')->addSuccess($message);
            }
        } catch (Exception $e) {
            Mage::helper('kariboo_shipping')->log($e->getMessage(), Zend_Log::ERR);
            $message = Mage::helper('kariboo_shipping')->__("Some of the selected orders are not ready to be shipped or have already been shipped, operation canceled.");
            Mage::getSingleton('core/session')->addError($message);
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Zips all undownloaded labels and gives downloadlink.
     */
    public function dowloadAllUndownloadedAction()
    {
        $orderIds = $this->getRequest()->getParam('entity_id');
        try {
            $generated_name = Mage::getModel('kariboo_shipping/adminhtml_kariboogrid')->processUndownloadedLabels($orderIds);
            if (!$generated_name) {
                $message = Mage::helper('kariboo_shipping')->__('No undownloaded labels found.');
                Mage::getSingleton('core/session')->addError($message);
                $this->_redirect('*/*/index');
            } else {
                $message = Mage::helper('kariboo_shipping')->__('Successfully exported order(s). Download the file here: %s',
                    ' <a id="downloadzip" href="'
                    . Mage::helper('adminhtml')->getUrl('*/*/downloadZip', array('file_name' => $generated_name)) . '" target="_blank">'
                    . 'kariboo_undownloaded.zip' . '</a>');
                Mage::getSingleton('core/session')->addSuccess($message);
                $this->_redirect('*/*/index');
            }
        } catch (Exception $e) {
            Mage::helper('kariboo_shipping')->log($e->getMessage(), Zend_Log::ERR);
            $message = Mage::helper('kariboo_shipping')->__("The file(s) could not be downloaded, please check your Kariboo logs.");
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('*/*/index');
        }
    }

    /**
     * Download responseAction for the zip
     */
    public function downloadZipAction()
    {
        $filename = $this->getRequest()->getParam('file_name');
        $fileLocation = Mage::getBaseDir('media') . "/kariboo/orderlabels/zips/" . $filename;
        if (file_exists($fileLocation)) {
            $this->_prepareDownloadResponse('kariboo_undownloaded.zip', file_get_contents($fileLocation));
        } else {
            $message = Mage::helper('kariboo_shipping')->__("The requested file does not exist, it is either removed or not readable.");
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('*/*/index');
        }
    }
}