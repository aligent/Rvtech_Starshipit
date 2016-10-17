<?php
class Rvtech_Starshipit_Helper_Starship extends Mage_Core_Helper_Abstract
{


    public $_wsdl = 'https://app1.starshipit.com/OrdersService.svc?singleWsdl';

    public $_syncMsg = '';

    /**
     * 
     * @return php soap client object
     */

    protected function _soapClient() {

        $wsdl = $this->_wsdl;
        return new SoapClient($wsdl, array('trace' => 1));
    }


    protected function _getAuthDetails(){

        return Mage::getModel('shipit/orders')->getAuthDetails();
    }

    /**
     * Call to Validate method of Soap
     * @return string
     */

    public function validateUser($authTo = array()) {
        
        $soap = $this->_soapClient();
        if(!empty($authTo)) {
					Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
					Mage::getModel('core/config')
								->saveConfig('shipit_options/group1/username', $authTo['userName']);
					Mage::getModel('core/config')
								->saveConfig('shipit_options/group1/api_key', $authTo['apiKey']);
        }else {
					$authTo = $this->_getAuthDetails();
        }
        
        
        $validate = $soap->__soapCall('Validate',array(
                    'Validate' => $authTo,
        ));        

        $result = $validate->ValidateResult;

        return $result;
    }

    /**
     * Format the Post Data.
     * 
     * @return array
     */

    public function arrangePostData($params = array()){
        $postData = array();
        $postData['username'] = $params['groups']['group1']['fields']['username']['value'];
        $postData['api_key'] = $params['groups']['group1']['fields']['api_key']['value'];
        $postData['update_orders'] = $params['groups']['group1']['fields']['update_orders']['value'];
        $postData['sync_orders_yesno'] = $params['groups']['group1']['fields']['sync_orders_yesno']['value'];
        $postData['sync_orders'] = $params['groups']['group1']['fields']['sync_orders']['value'];
        return $postData;
    }

    /**
     * Prepare an array in Magento acceptable form 
     * from the existing orders returned via Starship
     * @return array
     */

    public function finalOrderArrOverStarShip($orders) {

        $finOrderArr = array();        
        $ordersArr = (array) $orders;
        if(isset($ordersArr[0])){
            foreach ($ordersArr as $order) {
                $finOrderArr[$order->Id] = date("Y-m-d H:i:s", strtotime($order->LastUpdate));
            }            
        }
        else{
            $finOrderArr[$ordersArr['Id']] = date("Y-m-d H:i:s", strtotime($ordersArr['LastUpdate']));
        }

        return $finOrderArr;

    }

    /**
     * Call to GetExisting method of soap-server.
     * Check the existing orders at Starship
     * @return object
     */
    public function getExistingOrders($order = array()) {

        $soap = $this->_soapClient();
        $orders = $soap->__soapCall('GetExisting',array(
                    'GetExisting' => array('existing' => $order),
        ));        

        return $orders;
    }

    /**
     * Call to soap method AddShipment.
     * For adding/sync oredrs to the StarShip
     * @return string
     */

    public function addShipment($order = array()){

        $soap = $this->_soapClient();
        $orders = $soap->__soapCall('AddShipment',array(
                    'AddShipment' => array('orders' => $order),
        ));        

        return $orders;

    }

    /**
     * Check condition for Writing Tracking info and 
     * creating shippment in Magento
     * @return boolean
     */

    public function checkCondForMagentoWritebacks($response) {

        $success = $response->AddShipmentResult;
        $update  = Mage::getModel('shipit/orders')->needToUpdateOredrsInMage();

        if($success === 'Success' && ($update)) {
            return true;
        }

        return false;

    }

    /**
     * Call to GetMagentoWritebacks method of Soap service
     * call only if "Update orders to complete once shipped" is set to Yes
     * Return the Tracking info ONLY ONECE
     * @return object
     */

    public function getMagentoWritebacks(){
        $soap = $this->_soapClient();
        $authTo = $this->_getAuthDetails();
        $orders = $soap->__soapCall('GetMagentoWritebacks',array(
                    'GetMagentoWritebacks' => $authTo
        ));        

        return $orders;

    }

}
