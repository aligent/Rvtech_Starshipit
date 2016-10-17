<?php
class Rvtech_Starshipit_Model_Resource_Note_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('shipnote/note');
    }
}
