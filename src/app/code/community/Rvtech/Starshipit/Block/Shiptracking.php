<?php
class Rvtech_Starshipit_Block_Shiptracking extends Mage_Core_Block_Template
{
    public function _prepareLayout()
    {
		    return parent::_prepareLayout();
    }
    
    public function getShipTracking()     
    { 
        if (!$this->hasData('shiptracking')) {
            $this->setData('shiptracking', Mage::registry('current_order'));
        }
        return $this->getData('shiptracking');
        
    }
    
    public function getTrackInfo($order)
    {
        $shipTrack = array();
        if ($order) {
            $shipments = $order->getShipmentsCollection();
            foreach ($shipments as $shipment){
                $increment_id = $shipment->getIncrementId();
                $tracks = $shipment->getTracksCollection();

                $trackingInfos = array();
                foreach ($tracks as $track) {
                    $trackingInfos[] = $track->getNumberDetail();
                }
                $shipTrack[$increment_id] = $trackingInfos;
            }
        }
        return $shipTrack;
    }
    
    public function getShipItTrackInfo($orderId)
    {
        try {
            $shipItApiKey = Mage::helper('shipnote')->getShipItApiKey();
            if ($shipItApiKey == "") {
              return null;
            }
            
            $url = 'https://api.shipit.click/tracking?integration=magento&format=json';
            $post_data = '{"track":"","apikey":"' . $shipItApiKey . '","orderNumber":"' . $orderId . '","returnHtml":false}';
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
            $tracking_obj = $json_obj->{'tracking'};

            return $tracking_obj;
        }
        catch (Exception $e) {
            Mage::log($e->getMessage());
        }
        return null;
    }
}