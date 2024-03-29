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
 * Class Kariboo_Shipping_Block_Adminhtml_Sales_Order_Grid
 */
class Kariboo_Shipping_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructs the grid and sets basic parameters.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('kariboo_shipping_grid');
        $this->setDefaultSort('created_at');
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
        $collection = Mage::getResourceModel('sales/order_grid_collection');
        $collection->getSelect()->join(Mage::getConfig()->getTablePrefix() . 'sales_flat_order as sfo', 'sfo.entity_id=`main_table`.entity_id', array(
            'shipping_method' => 'shipping_method',
            'total_qty_ordered' => 'ROUND(total_qty_ordered,0)',
            'kariboo_label_exported' => 'kariboo_label_exported',
            'kariboo_label_exists' => 'kariboo_label_exists'
        ));
        $collection->addAttributeToFilter('shipping_method', array('like' => '%kariboo%'));
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
        $currency = (string)Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);
        $this->addColumn('real_order_id', array(
            'header' => $helper->__('Order #'),
            'width' => '80px',
            'type' => 'text',
            'index' => 'increment_id',
            'filter_index' => 'main_table.increment_id'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => $helper->__('Purchased From (Store)'),
                'index' => 'store_id',
                'type' => 'store',
                'store_view' => true,
                'display_deleted' => true,
                'filter_index' => 'sfo.store_id'
            ));
        }
        $this->addColumn('created_at', array(
            'header' => $helper->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
            'filter_index' => 'main_table.created_at'
        ));

        $this->addColumn('billing_name', array(
            'header' => $helper->__('Bill to Name'),
            'index' => 'billing_name',
            'filter_index' => 'main_table.billing_name'
        ));
        $this->addColumn('shipping_name', array(
            'header' => $helper->__('Ship to Name'),
            'index' => 'shipping_name',
            'filter_index' => 'main_table.shipping_name'
        ));
        $this->addColumn('base_grand_total', array(
            'header' => $helper->__('G.T. (Base)'),
            'index' => 'base_grand_total',
            'type' => 'currency',
            'currency' => 'base_currency_code',
            'filter_index' => 'main_table.base_grand_total'
        ));

        $this->addColumn('grand_total', array(
            'header' => $helper->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'filter_index' => 'main_table.grand_total'
        ));

        $this->addColumn('total_qty_ordered', array(
            'header' => $helper->__('# of Items'),
            'type' => 'int',
            'index' => 'total_qty_ordered',
            'width' => '100px',
        ));

        $this->addColumn('status', array(
            'header' => $helper->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
            'filter_index' => 'main_table.status'
        ));
        $this->addColumn('kariboo_label_exists', array(
            'header' => $helper->__('Kariboo! Label'),
            'index' => 'kariboo_label_exists',
            'type' => 'options',
            'width' => '100px',
            'options' => array(
                0 => 'No',
                1 => 'Yes',
            )
        ));
        $this->addColumn('kariboo_label_exported', array(
            'header' => $helper->__('Label Downloaded'),
            'index' => 'kariboo_label_exported',
            'type' => 'options',
            'width' => '100px',
            'options' => array(
                0 => 'No',
                1 => 'Yes',
            )
        ));
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                array(
                    'header' => $helper->__('Action'),
                    'width' => '50px',
                    'type' => 'action',
                    'getter' => 'getId',
                    'actions' => array(
                        array(
                            'caption' => $helper->__('View'),
                            'url' => array('base' => '*/sales_order/view', 'params' => array('karibooReturn' => '1')),
                            'field' => 'order_id',
                        )
                    ),
                    'filter' => false,
                    'sortable' => false,
                    'index' => 'stores',
                    'is_system' => true,
                ));
        }
        $this->addExportType('*/*/exportKaribooOrdersCsv', $helper->__('CSV'));
        $this->addExportType('*/*/exportKaribooOrdersExcel', $helper->__('Excel XML'));
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
     * Prepares Massactions for the grid.
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('`main_table`.entity_id');
        $this->getMassactionBlock()->setFormFieldName('entity_id');
        $this->getMassactionBlock()->addItem('generateAndComplete', array(
            'label' => Mage::helper('kariboo_shipping')->__('Generate Label and Complete'),
            'url' => $this->getUrl('*/*/generateAndComplete'),
            'confirm' => Mage::helper('kariboo_shipping')->__('Generating a label can take up to 1 second per label, please be patient during this process. It can take up to maximum 2 minutes. Do you want to continue?')
        ));
        $this->getMassactionBlock()->addItem('dowloadAllUndownloaded', array(
            'label' => Mage::helper('kariboo_shipping')->__('Download all undownloaded'),
            'url' => $this->getUrl('*/*/dowloadAllUndownloaded'),
        ));
        return $this;
    }


    /**
     * Generate rowurl.
     *
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('*/sales_order/view', array('order_id' => $row->getId(), 'karibooReturn' => '1'));
        }
        return false;
    }
}