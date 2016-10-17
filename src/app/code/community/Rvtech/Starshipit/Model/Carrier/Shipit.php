<?php
class Rvtech_Starshipit_Model_Carrier_Shipit extends Mage_Shipping_Model_Carrier_Abstract
implements Mage_Shipping_Model_Carrier_Interface {
	protected $_code = 'shipit';

	public function collectRates(Mage_Shipping_Model_Rate_Request $request)
	{
		if (!Mage::getStoreConfig('carriers/'.$this->_code.'/active')) {
			return false;
		}	       
    $price = 0;
    $doCalulate = $this->getConfigData('calculate');
    if ($doCalulate==0)
    {
      $price = $this->getConfigData('price');
    }
    else{
        try {
            $shipItApiKey = Mage::helper('shipnote')->getShipItApiKey();
            if ($shipItApiKey == "") {
              return false;
            }
            $origCountryId = $request->getCountryId(); //Package Source Country ID
            $origRegionId = $request->getRegionId(); //Package Source Region ID
            $origRegionCode = $request->getRegion(); //Package Source Region Code
            $origCity = $request->getCity(); //Package Source City
            $origPostcode = $request->getPostcode(); //Package Source Post Code
             
            $destCountryId = $request->getDestCountryId();
            $destCountry = $request->getDestCountry();
            $destRegion = $request->getDestRegionId();
            $destRegionCode = $request->getDestRegionCode();
            $destFullStreet = $request->getDestStreet();
            $destStreet = "";
            $destSuburb = "";
            $destCity = $request->getDestCity();
            $destPostcode = $request->getDestPostcode();
            
            $destFullStreetArray = explode("\n", $destFullStreet);
            if ($destFullStreetArray[0] !== false)
              $destStreet = $destFullStreetArray[0];
            if ($destFullStreetArray[1] !== false)
              $destSuburb = $destFullStreetArray[1];   
            
            $packageValue = $request->getPackageValue(); //Dest Package Value                  
            $packageValueDiscout = $request->getPackageValueWithDiscount(); //Dest Package Value After Discount
            $packageWeight = $request->getPackageWeight() * 1000; //Package Weight (grams)
            $packageCurrency = $request->getPackageCurrency();
            
            $url = 'https://api.shipit.click/rate?apiKey=' . $shipItApiKey . '&integration=magento&format=json';
            $post_data = '{
                            "rate": {
                              "origin": {
                                "country": "' . $origCountryId . '",
                                "postal_code": "' . $origPostcode . '",
                                "province": "' . $origRegionCode . '",
                                "city": "' . $origCity . '",
                                "name": null,
                                "address1": null,
                                "address2": null,
                                "address3": null,
                                "phone": null,
                                "fax": null,
                                "address_type": null,
                                "company_name": null
                              },
                              "destination":{  
                                "country": "' . $destCountryId . '",
                                "postal_code": "' . $destPostcode . '",
                                "province": "' . $destRegionCode . '",
                                "city": "' . $destCity . '",
                                "name": null,
                                "address1": "' . $destStreet . '",
                                "address2": "' . $destSuburb . '",
                                "address3": null,
                                "phone": null,
                                "fax": null,
                                "address_type": null,
                                "company_name": null
                              },
                              "items":[
                                {
                                  "name": "Total Items",
                                  "sku": null,
                                  "quantity": 1,
                                  "grams": ' . $packageWeight . ' ,
                                  "price": ' . $packageValue . ',
                                  "vendor": null,
                                  "requires_shipping": true,
                                  "taxable": true,
                                  "fulfillment_service": "manual"
                                }
                              ],
                              "currency": "' . $packageCurrency->getCurrencyCode() . '"
                            }
                          }';
            $contentLength = strlen($post_data);
                              
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));           
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $response = curl_exec($ch);
            curl_close($ch);
        
            $json_obj = json_decode($response);
            $rates_obj = $json_obj->{'rates'};
                                   
            $handling = Mage::getStoreConfig('carriers/'.$this->_code.'/handling');
        		$result = Mage::getModel('shipping/rate_result');
        		$show = true;
        		if($show){
              if(sizeof($rates_obj) > 0) {
                foreach($rates_obj as $rate) {
                  if(is_object($rate)) {
                    // Add shipping option with shipping price
                    $method = Mage::getModel('shipping/rate_result_method');
              			$method->setCarrier($this->_code);
              			$method->setMethod($this->_code . $rate->{'service_code'});
              			$method->setCarrierTitle($this->getConfigData('title'));
              			$method->setMethodTitle($rate->{'service_name'});
              			$method->setPrice($rate->{'total_price'});
              			$method->setCost(0);      
              			$result->append($method);
                  }
                }
              }
        		}else{
        			$error = Mage::getModel('shipping/rate_result_error');
        			$error->setCarrier($this->_code);
        			$error->setCarrierTitle($this->getConfigData('name'));
        			$error->setErrorMessage($this->getConfigData('specificerrmsg'));
        			$result->append($error);
        		}
        }
        catch (Exception $e) {
            Mage::log($e->getMessage());
        }
    }

		return $result;
	}
	public function getAllowedMethods()
	{
		return array('shipit'=>$this->getConfigData('name'));
	}

	public function isTrackingAvailable()
	{
	    return true;
	}  

	public function getTrackingInfo($tracking)
	{
        $status = Mage::getModel('shipping/tracking_result_status');
        $status->setCarrier('shipit');
        $status->setCarrierTitle($this->getConfigData('title'));
        $status->setTracking($tracking);
        $status->setPopup(1);
        $status->setUrl("https://app.shipit.click/track-your-parcel?l=".$tracking);
        return $status;           
    }  
}