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
 * Class Kariboo_Shipping_Block_Adminhtml_Sales_Order_View_Tab_Returnbarcode
 */
class Kariboo_Shipping_Block_Adminhtml_Sales_Order_View_Tab_Returnbarcode
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    /**
     * Constructs the block
     *
     */
    public function __construct(){
        parent::__construct();
        $this->setId('kariboo_returnbarcode_grid');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->_emptyText = Mage::helper('adminhtml')->__('This order did not (yet) generate labels which automatically returned return label barcodes.
                                                           This	feature	might not be active for your account. Please refer to the documentation	or your Kariboo!
                                                           accountmanager for more information.');
    }

    /**
     * prepare collection to use for the grid.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('sales/order_shipment')
            ->getCollection()
            ->addFieldToFilter('order_id',array('eq' => $this->getOrder()->getId()))
            ->addFieldToFilter('kariboo_return_barcode',array('notnull'=>1));
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }
    /**
     * prepare columns used in the grid.
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $helper = Mage::helper('kariboo_shipping');
        $this->addColumn('increment_id', array(
            'header' => $helper->__('Shipment'),
            'type' => 'text',
            'index' => 'increment_id',
            'filter_index' => 'main_table.increment_id'
        ));
        $this->addColumn('returnbarcode', array(
            'header' => $helper->__('Barcode #'),
            'type' => 'text',
            'index' => 'kariboo_return_barcode',
            'filter_index' => 'main_table.kariboo_return_barcode'
        ));
        return parent::_prepareColumns();
    }

    /**
     * Gets grid url for callbacks.
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * Generate rowurl.
     *
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return false;
    }

    /**
     * Returns tab label.
     *
     * @return string
     */
    public function getTabLabel() {
        return Mage::helper('kariboo_shipping')->__('Kariboo! Return Barcodes');
    }

    /**
     * Returns tab title.
     *
     * @return string
     */
    public function getTabTitle() {
        return Mage::helper('kariboo_shipping')->__('Kariboo! Return Barcodes');
    }

    /**
     * Checks if tab can be shown.
     *
     * @return bool
     */
    public function canShowTab() {
        return true;
    }

    /**
     * Checks if the tab has to be hidden.
     *
     * @return bool
     */
    public function isHidden() {
        return false;
    }

    /**
     * Returns the order object.
     *
     * @return mixed
     */
    public function getOrder(){
        return Mage::registry('current_order');
    }
}