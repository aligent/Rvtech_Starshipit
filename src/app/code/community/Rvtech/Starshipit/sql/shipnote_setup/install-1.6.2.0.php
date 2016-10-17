<?php

/**
 * @var Rvtech_Starshipit_Model_Resource_Setup
 */
$installer = $this;

/**
 * Create table: shipnote_note
 */
if (false === $installer->getConnection()->isTableExists($installer->getTable('shipnote/note'))) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('shipnote/note'))
        ->addColumn('note_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true
        ),'Note Id')
        ->addColumn('signature_required', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, 
        array(
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
        ), 'Signature Required')
        ->addColumn('authority_to_leave', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, 
        array(
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
        ), 'Authority to Leave')
        ->addColumn('delivery_instructions', Varien_Db_Ddl_Table::TYPE_TEXT, 1024, array(), 'Delivery Instructions');
    $installer->getConnection()->createTable($table);
}

$installer->addAttribute('quote', 'ship_note', array('type' => 'text'));
$installer->addAttribute('order', 'ship_note_id', array('type' => 'int'));
