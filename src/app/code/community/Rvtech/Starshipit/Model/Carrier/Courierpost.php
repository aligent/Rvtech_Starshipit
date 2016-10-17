<?php
class Rvtech_Starshipit_Model_Carrier_CourierPost extends Mage_Shipping_Model_Carrier_Abstract
implements Mage_Shipping_Model_Carrier_Interface {
	protected $_code = 'courierpost';

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
            $shipItApiKey = Mage::helper('shipnote')->getShipItApiKey();                      
            $destCountryId = $request->getDestCountryId();
            $destCountry = $request->getDestCountry();
            $destRegion = $request->getDestRegionId();
            $destRegionCode = $request->getDestRegionCode();
            $destStreet = $request->getDestStreet();
            $destCity = $request->getDestCity();
            $destPostcode = $request->getDestPostcode();
            $packageWeight = $request->getPackageWeight();
            $packageheight = $request->getPackageHeight();
            $packagewidth = $request->getPackageWidth();
            $packagedepth = $request->getPackageDepth();
            
            $params = array(
               "apiKey" => $shipItApiKey,
               "carrierid"=>0,
               "street"=>$destStreet,
               "suburb"=>"",
               "city"=> $destCity,
               "postcode"=>$destPostcode,
               "state"=>$destRegion,
               "country"=>$destCountry,
               "countryId"=>$destCountryId,
               "weight"=>$packageWeight,
               "height"=>$packageheight,
               "width"=>$packagewidth,
               "depth"=>$packagedepth
               );
            try{
            // Call web service.
            $wsdl = 'https://app.shipit.click/shipment.svc?WSDL';
            $client = new SoapClient($wsdl, array(
                'cache_wsdl'    => WSDL_CACHE_NONE, 
                'cache_ttl'     => 86400, 
                'trace'         => true,
                'exceptions'    => true,
            ));
            
            $result = $client->__soapCall("GetQuote", array($params));
            $quoteResponse = $result->GetQuoteResult;
            //Mage::log("Price web:".$quoteResponse->Price, null, ShipIt.log);
            $price = $quoteResponse->Price;
            }
            catch (Exception $e)
            {
                Mage::log($e->getMessage(), null, ShipIt.log);
            }
        }
       		
		$handling = Mage::getStoreConfig('carriers/'.$this->_code.'/handling');
		$result = Mage::getModel('shipping/rate_result');
		$show = true;
		if($show){

			$method = Mage::getModel('shipping/rate_result_method');
			$method->setCarrier($this->_code);
			$method->setMethod($this->_code);
			$method->setCarrierTitle($this->getConfigData('title'));
			$method->setMethodTitle($this->getConfigData('name'));
			$method->setPrice($price);
			$method->setCost($price);
			$result->append($method);

		}else{
			$error = Mage::getModel('shipping/rate_result_error');
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('name'));
			$error->setErrorMessage($this->getConfigData('specificerrmsg'));
			$result->append($error);
		}
		return $result;
	}
	public function getAllowedMethods()
	{
		return array('courierpost'=>$this->getConfigData('name'));
	}

	public function isTrackingAvailable()
	{
	    return true;
	}  

	public function getTrackingInfo($tracking)
	{
        $status = Mage::getModel('shipping/tracking_result_status');
        $status->setCarrier($this->_code);
        $status->setCarrierTitle($this->getConfigData('title'));
        $status->setTracking($tracking);
        $status->setPopup(1);
        $status->setUrl("http://trackandtrace.courierpost.co.nz/search/".$tracking);
        return $status;           
    }  
}