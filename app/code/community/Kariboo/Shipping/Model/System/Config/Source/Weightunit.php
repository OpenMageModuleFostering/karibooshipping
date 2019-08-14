<?php
/**
 * Created by PHPro
 *
 * @package      DPD
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */
/**
 * Class Kariboo_Shipping_Model_System_Config_Source_Weightunit
 */
class Kariboo_Shipping_Model_System_Config_Source_Weightunit
{
    /**
     * Options getter.
     * Returns an option array for unit weight to pass to the webservice.
     *
     * @return array
     *
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 100, 'label' => Mage::helper('kariboo_shipping')->__('kg')),
            array('value' => 0.1, 'label' => Mage::helper('kariboo_shipping')->__('g')),
            array('value' => 45.3592, 'label' => Mage::helper('kariboo_shipping')->__('lb')),
        );
    }

    /**
     * Get options in "key-value" format.
     * Returns an array for unit weight to pass to the webservice. (Magento basically expects both functions)
     *
     * @return array
     *
     */
    public function toArray()
    {
        return array(
            '100' => Mage::helper('kariboo_shipping')->__('kg'),
            '0,1' => Mage::helper('kariboo_shipping')->__('g'),
            '45.3592' => Mage::helper('kariboo_shipping')->__('lb')
        );
    }

}