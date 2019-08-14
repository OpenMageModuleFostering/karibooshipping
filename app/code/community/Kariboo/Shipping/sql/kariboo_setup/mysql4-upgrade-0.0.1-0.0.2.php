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

//create the return label table
$table_returnLabel = $installer->getConnection()->newTable($installer->getTable('kariboo_shipping_returnlabel'))
    ->addColumn('label_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
        'identity' => true,
    ), 'Label unique ID')
    ->addColumn('label_barcode', Varien_Db_Ddl_Table::TYPE_VARCHAR,255,array(
        'nullable' => false,
        'default' => "",
    ),"Barcode of the Label")
    ->addColumn('label_pdf_path', Varien_Db_Ddl_Table::TYPE_VARCHAR,255,array(
        'nullable' => false,
    ),"Local path of the pdf file")
    ->addColumn('order_id',Varien_Db_Ddl_Table::TYPE_INTEGER,11,array(
        'nullable' => false,
    ),"Id of the order")
    ->addColumn('date_created', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
    ), 'Creation Date')
    ->setComment('Kariboo! Shipping Return Labels');
$installer->getConnection()->createTable($table_returnLabel);

//add the tablerate table
$table_tableRate = $installer->getConnection()->newTable($installer->getTable('kariboo_shipping_tablerate'))
    ->addColumn('pk', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
        'identity' => true,
    ), 'Primary key')
    ->addColumn('website_id',Varien_Db_Ddl_Table::TYPE_INTEGER,11,array(
        'nullable' => false,
        'default' => '0',
    ),"Website Id")
    ->addColumn('dest_country_id', Varien_Db_Ddl_Table::TYPE_VARCHAR,4,array(
        'nullable' => false,
        'default' => '0',
    ),"Destination coutry ISO/2 or ISO/3 code")
    ->addColumn('dest_region_id', Varien_Db_Ddl_Table::TYPE_INTEGER,11,array(
        'nullable' => false,
        'default' => '0',
    ),"Destination Region Id")
    ->addColumn('dest_zip', Varien_Db_Ddl_Table::TYPE_VARCHAR,10,array(
        'nullable' => false,
        'default' => "*",
    ),"Destination Post Code (Zip)")
    ->addColumn('condition_name', Varien_Db_Ddl_Table::TYPE_VARCHAR,20,array(
        'nullable' => false,
    ),"Rate Condition name")
    ->addColumn('condition_value', Varien_Db_Ddl_Table::TYPE_DECIMAL,array(12,4),array(
        'nullable' => false,
        'default' => "0.0000",
    ),"Rate condition value")
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL,array(12,4),array(
        'nullable' => false,
        'default' => "0.0000",
    ),"Price")
    ->addColumn('cost', Varien_Db_Ddl_Table::TYPE_DECIMAL,array(12,4),array(
        'nullable' => false,
        'default' => "0.0000",
    ),"Cost")
    ->setComment('Kariboo! Shipping TableRate');
$installer->getConnection()->createTable($table_tableRate);

//sales/shipment
$installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'kariboo_label_exported', "int(11) null");
$installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'kariboo_label_path', "varchar(255) null default ''");

//sales/quote
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'kariboo_spotid', "varchar(255) null");

//sales/order
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'kariboo_spotid', "varchar(255) null");
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'kariboo_label_exported', "bool null default 0");
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'kariboo_label_exists', "bool null default 0");

$installer->endSetup();