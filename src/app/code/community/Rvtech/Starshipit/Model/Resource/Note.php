<?php
class Rvtech_Starshipit_Model_Resource_Note extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('shipnote/note', 'note_id');
    }
}
