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
 * Class Kariboo_Shipping_Model_System_Config_Source_Display
 */
class Kariboo_Shipping_Model_System_Config_Source_Display
{
    /**
     * Options getter.
     * Returns an option array for Google maps display.
     *
     * @return array
     *
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label' => Mage::helper('kariboo_shipping')->__('Overlay')),
            array('value' => 0, 'label' => Mage::helper('kariboo_shipping')->__('Inline')),
        );
    }

    /**
     * Get options in "key-value" format.
     * Returns an array for Google maps display. (Magento basically expects both functions)
     *
     * @return array
     *
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('kariboo_shipping')->__('Inline'),
            1 => Mage::helper('kariboo_shipping')->__('Overlay'),
        );
    }

}