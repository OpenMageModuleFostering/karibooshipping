<?php
/**
 * Created by PHPro
 *
 * @package      Kariboo
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */

/* @var $installer Mage_Customer_Model_Entity_Setup */
$installer = new Mage_Customer_Model_Entity_Setup;

$installer->addAttribute('customer', 'kariboo_shopid', array(
	'label'		=> 'Kariboo ShopID',
	'type'		=> 'varchar',
	'input'		=> 'text',
	'visible'	=> false,
	'required'	=> false,
	));

?>