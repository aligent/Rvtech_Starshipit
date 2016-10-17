<?php
class Rvtech_Starshipit_Model_Adminhtml_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     * @return Rvtech_Starshipit_Model_Adminhtml_Observer
     */
    public function adminhtml_sales_order_create_process_data(Varien_Event_Observer $observer)
    {
        try {
			$requestData = $observer->getEvent()->getRequest();
			$shipSignatureRequiredInt = $requestData['order']['ship_signature_required'];
			$shipAuthorityToLeaveInt = 0;
            $shipAuthorityToLeave = $requestData['order']['ship_authority_to_leave'];
            $shipNote = $requestData['order']['ship_note'];

			if (!empty($shipAuthorityToLeave)) {
	            if ($shipAuthorityToLeave == 'on') {
    	            $shipAuthorityToLeaveInt = 1;
	            }
	        }

			$shipNoteId = Mage::getModel('shipnote/note')
                    ->setDeliveryInstructions($shipNote)
                    ->setSignatureRequired($shipSignatureRequiredInt)
                    ->setAuthorityToLeave($shipAuthorityToLeaveInt)
                    ->save()
                    ->getId();

            if (isset($requestData['order']['ship_note'])) {
                $observer->getEvent()->getOrderCreateModel()->getQuote()
					->setShipNote($shipNoteId)
                    ->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * If the quote has a delivery note then lets save that note and
     * assign the id to the order
     *
     * @param Varien_Event_Observer $observer
     * @return Rvtech_Starshipit_Model_Observer
     */
    public function sales_convert_quote_to_order(Varien_Event_Observer $observer)
    {
        if ($shipNoteId = $observer->getEvent()->getQuote()->getShipNote()) {
            try {
                $observer->getEvent()->getOrder()
                    ->setShipNoteId($shipNoteId);

            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }
}
