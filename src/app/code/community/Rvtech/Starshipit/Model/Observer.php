<?php
class Rvtech_Starshipit_Model_Observer extends Mage_Core_Helper_Abstract
{
    const STARSHIP_BASE = 'https://app.shipit.click/Members/Search.aspx';
    protected $_noticeTitle = 'Starship Automatic Synchronization';
    protected $_noticeStatus; 

    public function syncOrdersNow() {
        $noticeMsg = '';		
        $helper = Mage::helper('shipit/starship');      
        $orderObj = Mage::getModel('shipit/orders');

        //get the configuration array (username, apikey etc.)
        $para = $orderObj->getDataForExistingOredrs();
        
        //get existing orders from Starship
        $existingOrderRes      = $helper->getExistingOrders($para);

        if(empty($existingOrderRes->GetExistingResult->ErrorMessage)){
            $resOrders      =   $existingOrderRes
                                ->GetExistingResult
                                ->Orders;
            $odersArr = array();
            try{
                if(isset($resOrders->ExistingOrder)){
                    $resExistingOrder = $resOrders->ExistingOrder;
                    $odersArr = $helper->finalOrderArrOverStarShip($resExistingOrder);
                }                    
            }catch(Exception $e){
                
                $this->_noticeStatus = 3;
                $noticeMsg .= $e->getMessage();
            }

            if(!empty($odersArr)) {
	            //get Orders array to sync with StarShip
	            $ordersToPass = $orderObj->prepareOrderToPass($odersArr);

	            if(empty($ordersToPass['Orders'])) {	               
	            	$noticeMsg .= 'No order found for sync to Starship';
	            	$this->_noticeStatus = 0;
	            }else{            
	                
	            	$noticeMsg .= 'Orders sync to Starship';
	            	$this->_noticeStatus = 1;	                
	            }

	            //Sync orders and store resonpse
	            $resShipSync = $helper->addShipment($ordersToPass);      

	            //Change Order State and add Track Info 
	            if($helper->checkCondForMagentoWritebacks($resShipSync)) {
	                $resWriteBack = $helper->getMagentoWritebacks(); 
	                //$this->getResponse()->setBody(print_r($resWriteBack));
	                if(isset($resWriteBack->GetMagentoWritebacksResult->WritebackStruct)){                
	                    $isTackadded = $orderObj->addTrackingInfo($resWriteBack);
	                    if($isTackadded) {
	                
			            	$noticeMsg .= 'Tracking Info SAVED';
			            	$this->_noticeStatus += 1;           
	                    }
	                }else{
	                
						$noticeMsg .= 'No tracking info found on Starship';
			            $this->_noticeStatus += 0;
	                }            
	            }else{
	            	if($this->_noticeStatus){
	            		$this->_noticeStatus += 1;
	            	}	
	            }
            }            
        }
        else {

            $this->_noticeStatus = 3;
            $noticeMsg .= $existingOrderRes->GetExistingResult->ErrorMessage;
        }
        $this->_addNotice($noticeMsg);
    }

    protected function _addNotice($msg)
    {
        $notice = Mage::getModel('adminNotification/inbox');

		    switch ($this->_noticeStatus) {
            case 0:
				        $notice->add(2,$this->_noticeTitle,$msg);
				        break;
            case 1:
                $notice->add(3,$this->_noticeTitle,$msg);
                break;
            case 3:
                $notice->add(1,$this->_noticeTitle,$msg);
                break;
            default:
                $notice->add(4,$this->_noticeTitle,$msg);
                break;
        }
    }

	  public function addMassAction($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $cur_url = Mage::helper('core/url')->getCurrentUrl();
        $starshipUrl = Mage::helper('core/url')->addRequestParam(
                self::STARSHIP_BASE,
                array(
                        'ReturnURL' => $cur_url,                        
                    )
            );
        if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
            && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $block->addItem('shipIt_multi', array(
                'label' => 'ShipIt',
                'shipIt_url' => $starshipUrl,

            ));
        }
    }
    /**
     * Take the note from post and and store it in the current quote.
     *
     * When the quote gets converted we will store the delivery note
     * and assign to the order
     *
     * @param Varien_Event_Observer $observer
     * @return Rvtech_Starshipit_Model_Observer
     */
    public function checkout_controller_onepage_save_shipping_method(Varien_Event_Observer $observer)
    {
        $shipSignatureRequiredInt = $observer->getEvent()->getRequest()->getParam('ship-signature-required');
		$shipAuthorityToLeaveInt = 0;
		$shipAuthorityToLeave = $observer->getEvent()->getRequest()->getParam('ship-authority-to-leave');
        $shipNote = $observer->getEvent()->getRequest()->getParam('ship-note');

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
        try {
			$observer->getEvent()->getQuote()
            	->setShipNote($shipNoteId)
				->save();
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
