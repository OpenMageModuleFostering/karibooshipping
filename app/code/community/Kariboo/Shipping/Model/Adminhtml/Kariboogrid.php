<?php

/**
 * Class Kariboo_Shipping_Model_Adminhtml_Kariboogrid
 */
class Kariboo_Shipping_Model_Adminhtml_Kariboogrid extends Mage_Core_Model_Abstract
{

    /**
     * @param $order
     * @return bool
     */
    public function generateAndCompleteOrder($order)
    {
        $shipmentCollection = $order->getShipmentsCollection();
        if ($shipmentCollection->count() > 0 && !$order->getKaribooLabelExists()) {
            return $this->_processAvailableShipments($order);
        } elseif (!$order->getKaribooLabelExists()) {
            return $this->_createKaribooShipment($order);
        } else {
            $message = Mage::helper('kariboo_shipping')->__("The order with id %s is not ready to be shipped or has already been shipped.", $order->getIncrementId());
            Mage::getSingleton('core/session')->addNotice($message);
            return false;
        }
    }

    /**
     * @param $order
     * @return bool
     */
    protected function _createKaribooShipment($order)
    {
        $shipment = $order->prepareShipment();
        $shipment->register();
        $weight = Mage::helper('kariboo_shipping')->calculateTotalShippingWeight($shipment);
        $shipment->setTotalWeight($weight);
        $labelArray = $this->_generateLabelAndReturnLabel($order, array($shipment), 1);
        $pdfBaseName = $labelArray[0]['pdfBasename'];
        $barCode = $labelArray[0]['barcode'];
        if (!$pdfBaseName) {
            $message = Mage::helper('kariboo_shipping')->__("Something went wrong while processing order %s, please check your error logs.", $order->getIncrementId());
            Mage::getSingleton('core/session')->addError($message);
            return false;
        } else {
            $explodeForCarrier = explode('_', $order->getShippingMethod(), 3);
            if (array_key_exists('returnBarcode', $labelArray[0])) {
                $shipment->setKaribooReturnBarcode($labelArray[0]['returnBarcode']);
            }
            $shipment->setKaribooLabelPath($pdfBaseName . ".pdf");
            $order->setIsInProcess(true);
            $order->addStatusHistoryComment(Mage::helper('kariboo_shipping')->__('Shipped with Kariboo generateLabelAndComplete'), true);
            $order->setKaribooLabelExists(1);
            $tracker = Mage::getModel('sales/order_shipment_track')
                ->setShipment($shipment)
                ->setData('title', 'Kariboo')
                ->setData('number', $barCode)
                ->setData('carrier_code', $explodeForCarrier[0])
                ->setData('order_id', $shipment->getData('order_id'));
            try {
                //save all objects in 1 transaction
                Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->addObject($tracker)
                    ->save();
            } catch (Exception $e) {
                Mage::helper('kariboo_shipping')->log($e->getMessage(), Zend_Log::ERR);
            }
            return 1;
        }
    }


    /**
     * @param $order
     * @return bool
     */
    protected function _processAvailableShipments($order)
    {
        $trackCollection = Mage::getResourceModel('sales/order_shipment_track_collection')
            ->addFieldToFilter('order_id', $order->getId())
            ->addFieldToFilter('carrier_code', 'kariboo');
        if ($trackCollection->count() > 0) {
            $counter = 0;
            $shipmentsArray = array();
            foreach ($trackCollection as $tracker) {
                if (!array_key_exists($tracker->getParentId(), $shipmentsArray)) {
                    $shipmentsArray[$tracker->getParentId()] = Mage::getResourceModel('sales/order_shipment_collection')->addFieldToFilter('entity_id', $tracker->getParentId())->getFirstItem();
                }
            }
            $labelArray = $this->_generateLabelAndReturnLabel($order, $shipmentsArray, $trackCollection->count());
            foreach ($trackCollection as $tracker) {
                $shipment = $shipmentsArray[$tracker->getParentId()];
                $pdfBaseName = $labelArray[$counter]['pdfBasename'];
                $barCode = $labelArray[$counter]['barcode'];
                if (!$pdfBaseName) {
                    $message = Mage::helper('kariboo_shipping')->__("Something went wrong while processing order %s, please check your error logs.", $order->getIncrementId());
                    Mage::getSingleton('core/session')->addError($message);
                    continue;
                } else {
                    try {
                        if (array_key_exists('returnBarcode', $labelArray[$counter])) {
                            $shipment->setKaribooReturnBarcode($labelArray[$counter]['returnBarcode']);
                        }
                        $shipment->setKaribooLabelPath($pdfBaseName . ".pdf");
                        $tracker->setData('number', $barCode);
                        Mage::getModel('core/resource_transaction')
                            ->addObject($shipment)
                            ->addObject($tracker)
                            ->save();
                    } catch (Exception $e) {
                        Mage::helper('kariboo_shipping')->log($e->getMessage(), Zend_Log::ERR);
                        continue;
                    }
                }
                $counter++;
            }
            $order->addStatusHistoryComment(Mage::helper('kariboo_shipping')->__('Shipped with Kariboo generateLabelAndComplete'), true);
            $order->setKaribooLabelExists(1);
            $order->save();
            return $counter;
        } else {
            $message = Mage::helper('kariboo_shipping')->__("The order with id %s only has non-Kariboo! shipments.", $order->getIncrementId());
            Mage::getSingleton('core/session')->addNotice($message);
            return false;
        }
    }


    /**
     * @param $order
     * @param $shipment
     * @param $count
     * @return array|bool
     */
    protected function _generateLabelAndReturnLabel($order, $shipment, $count)
    {
        $labelWebserviceCallback = Mage::getSingleton('kariboo_shipping/webservice')->getPickUpLabel($order, $shipment, $count);
        if ($labelWebserviceCallback) {
            $returnArray = array();
            $pdfLabel = $labelWebserviceCallback->LabelPdfs->LabelPdf;
            $counter = 0;
            foreach ($pdfLabel as $shipmentPdflabel) {
                $returnArray[$counter] = array('barcode' => $shipmentPdflabel->Barcode, 'pdfBasename' => Mage::helper('kariboo_shipping')->generatePdfAndSave($shipmentPdflabel->label, 'orderlabels', $order->getIncrementId() . "-" . $shipmentPdflabel->Barcode));
                if (isset($shipmentPdflabel->BarcodeReturn)) {
                    $returnArray[$counter]['returnBarcode'] = $shipmentPdflabel->BarcodeReturn;
                }
                $counter++;
            }
            return $returnArray;
        } else {
            return false;
        }
    }

    /**
     * Processes the undownloadable labels. (set mark and zip)
     *
     * @param $orderIds
     * @return bool|string
     */
    public function processUndownloadedLabels($orderIds)
    {
        $labelPdfArray = array();
        $i = 0;
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $exported = false;
            if (!$order->getKaribooLabelExported()) {
                $shippingCollection = Mage::getResourceModel('sales/order_shipment_collection')
                    ->setOrderFilter($order)
                    ->load();
                if (count($shippingCollection)) {
                    foreach ($shippingCollection as $shipment) {
                        if ($shipment->getKaribooLabelPath() != "" && file_exists(Mage::getBaseDir('media') . "/kariboo/orderlabels/" . $shipment->getKaribooLabelPath()) && $shipment->getKaribooLabelPath() != ".pdf") {
                            $labelPdfArray[] = Mage::getBaseDir('media') . "/kariboo/orderlabels/" . $shipment->getKaribooLabelPath();
                            $exported = true;
                        }
                    }
                    if ($exported) {
                        $order->setKaribooLabelExported(1)->save();
                    }
                }
            } else {
                $i++;
            }
        }
        if (!count($labelPdfArray)) {
            return false;
        }
        if ($i > 0) {
            $message = Mage::helper('kariboo_shipping')->__('%s orders already had downloaded labels.', $i);
            Mage::getSingleton('core/session')->addNotice($message);
        }
        $generated_name = date("Y_m_d_H_i_s_u") . "_undownloaded.zip";
        if (!is_dir(Mage::getBaseDir('media') . "/kariboo/orderlabels/zips/")) {
            mkdir(Mage::getBaseDir('media') . "/kariboo/orderlabels/zips/");
        }
        return $this->_zipLabelPdfArray($labelPdfArray, $generated_name, true);
    }

    /**
     * Zips the labels.
     *
     * @param array $files
     * @param string $generated_name
     * @param bool $overwrite
     * @return bool|string
     */
    protected function _zipLabelPdfArray($files = array(), $generated_name = '', $overwrite = false)
    {
        $destination = Mage::getBaseDir('media') . "/kariboo/orderlabels/zips/" . $generated_name;
        if (file_exists($destination) && !$overwrite) {
            return false;
        }
        $valid_files = array();
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        if (count($valid_files)) {
            $zip = new ZipArchive();
            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            foreach ($valid_files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            return $generated_name;
        } else {
            return false;
        }
    }

}