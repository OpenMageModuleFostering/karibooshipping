<?php
/**
 * Created by PHPro
 *
 * @package      Kariboo
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'kariboo_original_shippingaddress', "TEXT");

$installer->endSetup();