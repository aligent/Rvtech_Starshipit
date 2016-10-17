<?php
class Rvtech_Starshipit_Block_Adminhtml_Sales_Order_Note extends Mage_Adminhtml_Block_Sales_Order_View_Info
{
    /**
     * @var Rvtech_Starshipit_Model_Note
     */
    protected $_note;

    /**
     * @return Rvtech_Starshipit_Model_Note|null
     */
    public function getNote()
    {
        if (null === $this->_note) {
            $this->_note = Mage::getModel('shipnote/note')
                ->loadByOrder($this->getOrder());
        }
        return $this->_note;
    }
}
