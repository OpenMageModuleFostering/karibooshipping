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
 * Class Kariboo_Shipping_Block_Adminhtml_System_Config_Form_Export
 */
class Kariboo_Shipping_Block_Adminhtml_System_Config_Form_Export extends Mage_Adminhtml_Block_System_Config_Form_Field implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     */
    public function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $buttonBlock = Mage::app()->getLayout()->createBlock('adminhtml/widget_button');

        $params = array(
            'website' => $buttonBlock->getRequest()->getParam('website')
        );

        $data = array(
            'label' => Mage::helper('adminhtml')->__('Export CSV'),
            'onclick' => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl("adminhtml/karibooconfig/exportKaribooTablerates", $params) . 'conditionName/\' + $(\'carriers_kariboo_table_rate_condition\').value + \'/kariboospotstablerates.csv\' )',
            'class' => '',
            'id' => 'carriers_kariboo_export'
        );

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }
}