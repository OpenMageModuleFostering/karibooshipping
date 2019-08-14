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
 * Class Kariboo_Shipping_Block_Adminhtml_Sales_Order_View_Tab_Returnlabels
 */
class Kariboo_Shipping_Block_Adminhtml_Sales_Order_View_Tab_Returnlabels
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    /**
     * Constructs the block
     *
     */
    protected function _construct()
    {
        //parent::__construct();
        $this->setId('kariboo_returnlabel_grid');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * prepare collection to use for the grid.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('kariboo_shipping/returnlabel')
            ->getCollection()
            ->addFieldToFilter('order_id',array('eq' => $this->getOrder()->getId()));

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
        $this->addColumn('date_created', array(
            'header' => $helper->__('Date Created'),
            'type' => 'text',
            'index' => 'date_created',
            'filter_index' => 'main_table.date_created'
        ));
        $this->addColumn('label_barcode', array(
            'header' => $helper->__('Label Barcode #'),
            'type' => 'text',
            'index' => 'label_barcode',
            'filter_index' => 'main_table.label_barcode'
        ));
        $this->addColumn('label_pdf_path', array(
            'header' => $helper->__('Filename'),
            'index' => 'label_pdf_path',
            'filter_index' => 'main_table.label_pdf_path'
        ));
        $this->addColumn('Download', array(
                'header'    => $helper->__('Action'),
                'type'      => 'action',
                'getter'     => 'getLabelPdfPath',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Download'),
                        'url'     => Mage::getBaseUrl("media") . "kariboo/returnlabel/" .'$label_pdf_path',
                        'target'  => '_blank',
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'label_pdf_path',
            ));
        $this->addColumn('Email', array(
            'header'    => $helper->__('Email'),
            'type'      => 'action',
            'getter'     => 'getId',
            'actions'   => array(
                array(
                    'caption' => Mage::helper('catalog')->__('Send Email'),
                    'url'     => array(
                        'base'=>'*/karibooorder/sendEmail',
                    ),
                    'field'=> 'return_id'
                )
            ),
            'filter'    => false,
            'sortable'  => false,
            'index'     => '',
        ));
        return parent::_prepareColumns();
    }

    public function _afterToHtml($html)
    {
        $suphtml = "";
        if(Mage::app()->getRequest()->getParam('karibooReturn') || Mage::getSingleton('core/session')->getKaribooReturn() == 1){
            if(!Mage::getSingleton('core/session')->getKaribooReturn()){
                Mage::getSingleton('core/session')->setKaribooReturn(1);
            }
            $suphtml = '
            <script type="text/javascript">
                document.observe("dom:loaded", function (evt) {
                    $$(".form-buttons .back")[0].observe("click", function () {
                        setLocation("'.Mage::helper("adminhtml")->getUrl("adminhtml/karibooorder/").'");
                        evt.preventDefault();
                    });
                });
            </script>';
        }


        return $suphtml .$html;
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
        return Mage::helper('kariboo_shipping')->__('Kariboo! Return Labels');
    }

    /**
     * Returns tab title.
     *
     * @return string
     */
    public function getTabTitle() {
        return Mage::helper('kariboo_shipping')->__('Kariboo! Return Labels');
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