<?php

class Rvtech_Starshipit_Model_Orders extends Mage_Sales_Model_Order {

	protected $_User;
	
	protected $_APIKey;

	protected $_auth = array();

	protected $_orderInMage = array();

	protected $_orderInStarShip = array();

	protected $_dataForExistingOredrs = array();

	public function _getUserName() {

		return Mage::getStoreConfig('shipit_options/group1/username');
	}

	public function _getAPIKey() {

		return Mage::getStoreConfig('shipit_options/group1/api_key');
	}

	protected function _getUpdateOrderConfig() {

		return Mage::getStoreConfig('shipit_options/group1/update_orders');
	}

	protected function _getSyncOrderConfig() {

		return Mage::getStoreConfig('shipit_options/group1/sync_orders');
	}			

	protected function _getSyncOrderAutoConfig() {

		return Mage::getStoreConfig('shipit_options/group1/sync_orders_yesno');
	}			

	protected function _getFormattedTelephone($order){

		return str_replace('-', '', $order->getShippingAddress()->getData('telephone'));
	}

	protected function _saveStoreConfig($postData) {
	
		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		Mage::getModel('core/config')
					->saveConfig('shipit_options/group1/username', $postData['username']);
		Mage::getModel('core/config')
					->saveConfig('shipit_options/group1/api_key', $postData['api_key']);
		Mage::getModel('core/config')
					->saveConfig('shipit_options/group1/update_orders', $postData['update_orders']);
		Mage::getModel('core/config')
					->saveConfig('shipit_options/group1/sync_orders', $postData['sync_orders']);
		Mage::getModel('core/config')
					->saveConfig('shipit_options/group1/sync_orders_yesno', $postData['sync_orders_yesno']);															
	}
	

	public function getAuthDetails() {

        $this->_auth   = array(

        					'userName'	=>	$this->_getUserName(),
        					'apiKey'	=>	$this->_getAPIKey()
        				);		
        return $this->_auth;
	}
	

	public function getDataForExistingOredrs($postData = array()) {

		if(empty($postData))
		{
			return $this->_dataForExistingOredrs = array(

									'User'		=> $this->_getUserName(),
									'APIKey'	=> $this->_getAPIKey(),
									'Days'		=> $this->_getSyncOrderConfig(),
									'Orders'	=> $this->_getUpdateOrderConfig(),

					);			
		}

		$this->_saveStoreConfig($postData);
		
		$this->_dataForExistingOredrs = array(

								'User'		=> $postData['username'],
								'APIKey'	=> $postData['api_key'],
								'Days'		=> $postData['sync_orders'],
								'Orders'	=> $postData['update_orders'],

		);

		return $this->_dataForExistingOredrs;
	}

    /**
	* Convert date array to UTC 
	* for comaprison purpose
    * @return array
    */	

	protected function _formatDateUtc($dateArr = array()){

		$dateUtcArr = array();
		foreach ($dateArr as $id => $date) {
			$dateUtcArr[$id] = strtotime($date);
		}
		return $dateUtcArr;
	}

    /**
	* Convert date in Starship Accpetable Format
	* 
    * @return string
    */	
	
	protected function _formatDateStarShip($date){		
		
		return date('d/m/Y h:i:s A',strtotime($date));
	}

    /**
	* Check all oredrs in Magento with status Processing 
	* and set their Ids as key and Update_at as value in Protected array _orderInMage
    * @return none
    */

	protected function _checkOrdersInMage() {
		
		foreach ($this->getCollection() as $order) {
			if(strtolower($order->getStatusLabel()) === self::STATE_PROCESSING) {					
				$this->_orderInMage[$order->getData('entity_id')] = $order->getData('updated_at');
			}
		}		

	}

    /**
	* Prepare a final array of Oredrs via comparing Orders in Mage and Oredrs in Starship
	* 
    * @return array
    */


	protected function _checkOrderToProcess($orders = array()) {

		$ordersToProcess = array();
		$this->_checkOrdersInMage();
		$orderInStarShipUtc = $this->_formatDateUtc($this->_orderInStarShip);
		$orderInMageUtc = $this->_formatDateUtc($this->_orderInMage);

		foreach ($orderInMageUtc as $key => $value) {
			if (array_key_exists($key,$orderInStarShipUtc)) {
				if($value >= $orderInStarShipUtc[$key]){
					$ordersToProcess[] = $key;
				}				
			}else{
				$ordersToProcess[] = $key;
			}

		}
		
		return $ordersToProcess;
	}

    /**
	* Prepare Orders details to send over Starship
	* 
    * @return array
    */

	protected function _orderShippedArr($ordersToProcess = array()){

		$orderList = array();
		foreach ($this->getCollection() as $order) {

			if(in_array($order->getData('entity_id'),$ordersToProcess)) {
						
			$orderData['Id']					= $order->getData('entity_id');
			$orderData['SequenceNumber']		= $order->getData('entity_id');
			$orderData['SequenceGuid']			= null;
			$orderData['OrginalSeqNumber']		= 0;
			$orderData['AccountingAppId']		= 3;
			$orderData['ShippedDate']			= $this->_getOrderShipmentCreatedDate($order);
			$orderData['Date']					= $this->_formatDateStarShip($order->getData('created_at'));
			$orderData['To']					= $this->_getOrderTo($order);
			$orderData['OurRef']				= (string) $order->getData('increment_id');
			$orderData['TheirRef']				= (string) $order->getData('entity_id');
			$orderData['ConsigneeCode']			= '';
			$orderData['Email']					= (string) $order->getData('customer_email');
			$orderData['Telephone']				= (string) $this->_getFormattedTelephone($order);
			$orderData['AddressString']			= (string) $this->_getOrderShippingAddress($order,true);
			$orderData['ItemsString']			= (string) $this->_getOrderItemsStr($order);
			$orderData['TrackingNumber']		= '';
			$orderData['CarrierCode']			= 0;
			$orderData['ProductName']			= 'P';
			$orderData['ErrorMessage']			= '';
			$orderData['Status']				= 0;
			$orderData['TrackingCode']			= '';
			$orderData['Invoiced']				= '';
			$orderData['OrderValue']			= (float) $order->getData('total_qty_ordered');
			$orderData['OrderCurrency']			= (string) $order->getData('global_currency_code');
			$orderData['AddressChecked']		= true;
			$orderData['AddressValidated']		= true;
			$orderData['ShipmentDescription']	= "ShipmentDescription";			
			$orderData['Items']					= $this->_getOrderItemsArr($order);
			$orderData['AddressDetails']		= $this->_getOrderShippingAddress($order);
			$orderData['BillingAddress']		= (string) $this->_getOrderBillingAddress($order);
			$orderData['LastUpdatedatSource']	= (string) $this->_formatDateStarShip($order->getData('updated_at'));
			$orderList[] = $orderData;

		}
			
		}

		return $orderList;		
	}

    /**
	* Get shipment created date
	* 
    * @return string
    */

	protected function _getOrderShipmentCreatedDate($order){

		$date = '';
		if(!$order->canShip()){
			$shipData = $order->getShipmentsCollection()->getData();
			$date = $this->_formatDateStarShip($shipData[0]['created_at']);			
		}
		
		return $date;
	}

    /**
	* Get person name
	* 
    * @return string
    */

	protected function _getOrderTo($order){

			$firstName = $order->getShippingAddress()->getData('firstname');
			$lastName = $order->getShippingAddress()->getData('lastname');
			$name = $firstName." ".$lastName;
			return $name;
	}

    /**
	* Get country full name
	* 
    * @return string
    */

	protected function _getCountryNameByCode($country_code){

		return Mage::app()->getLocale()->getCountryTranslation($country_code);
	}

    /**
	* Get shippment address
	* 
    * @return string/array
    */

	protected function _getOrderShippingAddress($order, $string = false ) {
		
		$addArr 	= array();
		$AddressDTO = array();		
		if($string){
			$streetAdd = $order->getShippingAddress()->getData('street');
			$street = trim(preg_replace('/\s+/', ' ', $streetAdd));
			$city = $order->getShippingAddress()->getData('city');
			$postcode = $order->getShippingAddress()->getData('postcode');		
			$countryCode = $order->getShippingAddress()->getData('country_id');
			$country = $this->_getCountryNameByCode($countryCode);
			$addString = $street."k ".$city." ".$postcode." ".$country;
			return $addString;
		}

		$addArr['City'] = $order->getShippingAddress()->getData('city');
		$addArr['Company'] = $order->getShippingAddress()->getData('company');
		$addArr['Country'] = $this->_getCountryNameByCode($order->getShippingAddress()->getData('country_id'));
		$addArr['CountryId'] = $order->getShippingAddress()->getData('country_id');
		$addArr['Instructions'] = '';
		$addArr['PostCode'] = $order->getShippingAddress()->getData('postcode');
		$addArr['Region'] = $order->getShippingAddress()->getData('region');
		$addArr['State'] = '';
		$addArr['Street'] = $order->getShippingAddress()->getData('street');
		$addArr['Suburb'] = '';
		$AddressDTO['AddressDTO'] = $addArr;
		return $addArr;

	}	

    /**
	* Get billing addresss
	* 
    * @return string
    */

	protected function _getOrderBillingAddress($order) {
		
		$streetAdd = $order->getBillingAddress()->getData('street');
		$street = trim(preg_replace('/\s+/', ' ', $streetAdd));
		$city = $order->getBillingAddress()->getData('city');
		$postcode = $order->getBillingAddress()->getData('postcode');		
		$country = $order->getBillingAddress()->getData('country_id');
		$add = $street." ".$city." ".$postcode." ".$country;

		return $add;

	}

    /**
	* Get Oredrs Items as string
	* 
    * @return string
    */
	protected function _getOrderItemsStr($order) {

		$allItemsArr = $order->getAllVisibleItems();
		$itemsStr = '';
		$lastItemArr = end($allItemsArr);
		$count = count($allItemsArr);
		foreach ($allItemsArr as $item) {
			$itemsStr .= $item->getName();
			if( $count > 1 && $item->getId() != $lastItemArr->getId())
				$itemsStr .= ', ';
		}

		return $itemsStr;

	}

    /**
	* Get Order's items
	* 
    * @return array
    */

	protected function _getOrderItemsArr($order, $string = false) {

		$ItemsArr = array();
		$shipmentitems = array();	
		foreach ($order->getAllVisibleItems() as $item) {
			$ItemsArr['Description'] = (string)$item->getName();
			$ItemsArr['Country'] = (string)$order->getShippingAddress()->getData('country_id');;
			$ItemsArr['Price'] = (float)$item->getPrice();
			$ItemsArr['Quantity'] = (int)round($item->getQtyOrdered());	
			$shipmentitems['ShipmentItem'][] = $ItemsArr;
		}

		return $shipmentitems;

	}

    /**
	* Prepare final Orders Array with auth details to pass
	* over the star ship 
    * @return array
    */

	public function prepareOrderToPass($orders = array()) {

		$this->_orderInStarShip = $orders;
		$ordersToProcess = $this->_checkOrderToProcess($orders);
		$arrOrdersToPass['UserName'] = $this->_getUserName();
		$arrOrdersToPass['Password'] = $this->_getAPIKey();
		$arrOrdersToPass['Orders'] = $this->_orderShippedArr($ordersToProcess);
		

		return $arrOrdersToPass;


	}

	public function needToUpdateOredrsInMage(){

		return $this->_getUpdateOrderConfig();
	}



    /**
	* Create shippment and set the tracking info to ot
	* 
    * @return 
    */

	protected function _setAndSaveTrackingInfo($orderId,$orderCarrier,$orderTrackingNumber){
		
		foreach ($this->getCollection() as $order) {
			if($order->getData('entity_id') == $orderId && $order->canShip()){
				
				try {

					$shipment = $order->prepareShipment();
					$shipment->register();
					$order->setIsInProcess(true);
					$order->addStatusHistoryComment('Automatically SHIPPED by Starship.', false);
					$transactionSave = Mage::getModel('core/resource_transaction')
										->addObject($shipment)
										->addObject($shipment->getOrder())
										->save();

				    if($shipment->getId() != '') { 
				    	try{
					        $track = Mage::getModel('sales/order_shipment_track')
					                 ->setShipment($shipment)
					                 ->setData('number', $orderTrackingNumber)
					                 ->setData('carrier_code', strtolower($orderCarrier))
					                 ->setData('order_id', $shipment->getData('order_id'))
					                 ->save();
							return true;					                 
				    	}catch(Exception $e){

				    		Mage::getSingleton('core/session')
				    				->addError($this->__('Shipment added But Error while adding Tracking details Error is: ').$e->getMessage());
				    	}
					}
				}catch(Exception $e){
					$order->addStatusHistoryComment('Starship_Invoicer: Exception occurred during action. Exception message: '.$e->getMessage(), false);
					$order->save();
					Mage::getSingleton('core/session')->addError($this->__('Exception Occured: ').$e->getMessage());
				}

			}
		}
				
	}

	public function addTrackingInfo($trackingInfo) {

		$WritebackStruct = $trackingInfo->GetMagentoWritebacksResult->WritebackStruct;

		if(is_array($WritebackStruct)){

			foreach ($WritebackStruct as $orderTrackInfo) {
				$orderId = $orderTrackInfo->Sequence;
				$orderCarrier = $orderTrackInfo->Carrier;
				$orderTrackingNumber = $orderTrackInfo->TrackingNumber;			
				$this->_setAndSaveTrackingInfo($orderId,$orderCarrier,$orderTrackingNumber);
			}

			return true;

		}else{
				$orderId = $WritebackStruct->Sequence;
				$orderCarrier = $WritebackStruct->Carrier;
				$orderTrackingNumber = $WritebackStruct->TrackingNumber;
				$this->_setAndSaveTrackingInfo($orderId,$orderCarrier,$orderTrackingNumber);

			return true;
		}

		return false;


	}	
}
