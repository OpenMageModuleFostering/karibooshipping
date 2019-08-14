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

//sales/shipment/track
$installer->getConnection()->addColumn($installer->getTable('sales/shipment_track'),
    'kariboo_text',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable' => true,
        'default' => null,
        'comment' => "Tracking Status Text"
    ));

$installer->endSetup();