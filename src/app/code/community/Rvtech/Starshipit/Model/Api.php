<?php
class Rvtech_Starshipit_Model_Api extends Mage_Api_Model_Resource_Abstract
{
    public function info($orderId)
    {
        $shipNoteModel = Mage::getModel('shipnote/note')->loadByOrderId($orderId);
        if (!$shipNoteModel->getId()) {
            $this->_fault('not_exists');
            // If shipping note not found.
        }
        return $shipNoteModel->toArray();
        // We can use only simple PHP data types in webservices.
    }
}
