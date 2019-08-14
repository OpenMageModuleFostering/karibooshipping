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
 * Class Kariboo_Shipping_Block_Adminhtml_Sales_Download_Grid
 */
class  Kariboo_Shipping_Block_Adminhtml_Sales_Download_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructs the grid and sets basic parameters.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('kariboo_download_grid');
        $this->setDefaultDir('ASC');
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
        $collection = new Varien_Data_Collection();

        $pattern = Mage::getBaseDir('media') . "/kariboo/orderlabels/zips/*.zip";
        $i=1;
        foreach (array_reverse(glob($pattern),true) as $filename) {
            $obj = new Varien_Object();
            $obj->id = $i;
            $obj->filename = basename($filename);
            $collection->addItem($obj);
            $i++;
        }

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

        $this->addColumn('filename', array(
            'header' => Mage::helper('kariboo_shipping')->__('Filename'),
            'index' => 'filename',
            'filter' => false,
            'sortable' => false
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
        return Mage::getBaseUrl("media") . "kariboo/orderlabels/zips/".$row->getFilename();
    }
}