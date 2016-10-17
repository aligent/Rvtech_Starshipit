<?php
class Rvtech_Starshipit_Adminhtml_ShipItController extends Mage_Adminhtml_Controller_Action
{


    /**
     * Return some checking result
     *
     * @return void
     */
    public function testAction()
    {
		$params = array();
		$params = $this->getRequest()->getParams();
//		$this->getResponse()->setBody(print_r($params));
        //break;
		$autoTo = array(
					'userName'	=>	$params['groups']['group1']['fields']['username']['value'],
					'apiKey'	=>	$params['groups']['group1']['fields']['api_key']['value']
		);
//        $this->getResponse()->setBody(print_r($autoTo));				
				
        $helper = Mage::helper('shipit/starship');
        $resStarShip = $helper->validateUser($autoTo);
        if($resStarShip === 'Success'){
            Mage::getSingleton('core/session')->addSuccess('Valid UserName And APIKey');
        }
        else{
            Mage::getSingleton('core/session')->addError($resStarShip);   
            //$this->getResponse()->setBody(print_r($resStarShip));

        }
        
        Mage::app()->getResponse()->setBody(
            $this->getLayout()->getMessagesBlock()->getGroupedHtml()
        ); 
    }

     /**
     * will sync orders
     *
     * @return void
     */

    public function syncOrderAction(){


		$params = array();
		$params = $this->getRequest()->getParams();
		//$this->getResponse()->setBody($params);

		// $user = $params['user'];
		// $apikey = $params['apikey'];

    

        $helper = Mage::helper('shipit/starship');
        $postData = $helper->arrangePostData($params);
        //$this->getResponse()->setBody(print_r($postData));
        $orderObj = Mage::getModel('shipit/orders');

        //get the configuration array (username, apikey etc.)
        $para = $orderObj->getDataForExistingOredrs($postData);


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

                Mage::getSingleton('core/session')->addError($e->getMessage());
            }

            
            //get Orders array to sync with StarShip
            $ordersToPass = $orderObj->prepareOrderToPass($odersArr);

            if(empty($ordersToPass['Orders'])) {            
                Mage::getSingleton('core/session')->addNotice('No order found for sync to Starship');
            }else{            
                Mage::getSingleton('core/session')->addSuccess('Orders sync to Starship ');
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
                        Mage::getSingleton('core/session')->addSuccess('Tracking Info SAVED');
                    }
                }else{
                    Mage::getSingleton('core/session')->addNotice('No tracking info found on Starship');
                }            
            }
                        
        }else{

            Mage::getSingleton('core/session')->addError(
                $existingOrderRes->GetExistingResult->ErrorMessage
            );            
        }

        Mage::app()->getResponse()->setBody(
            $this->getLayout()->getMessagesBlock()->getGroupedHtml()
        );        
        
    }

}
