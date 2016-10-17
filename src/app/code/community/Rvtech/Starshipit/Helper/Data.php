<?php
class Rvtech_Starshipit_Helper_Data extends Mage_Core_Helper_Abstract
{
    const STORE_CONFIG_PATH_SHIPIT_API_KEY = 'shipnote_options/shipit_settings/shipit_api_key';
    const STORE_CONFIG_PATH_ENABLED        = 'shipnote_options/basic_settings/enabled';
    const STORE_CONFIG_PATH_FRONTEND_LABEL = 'shipnote_options/basic_settings/frontend_label';

    /**
     * @return string
     */
    public function getShipItApiKey()
    {
        return Mage::getStoreConfig(self::STORE_CONFIG_PATH_SHIPIT_API_KEY);
    }
    
    /**
     * @return bool
     */
    public function isEnabled()
    {
        if (false === parent::isModuleEnabled()) {
            return false;
        }
        return Mage::getStoreConfigFlag(self::STORE_CONFIG_PATH_ENABLED);
    }

    /**
     * @return string
     */
    public function getFrontendLabel()
    {
        return Mage::getStoreConfig(self::STORE_CONFIG_PATH_FRONTEND_LABEL);
    }
    
    public function getShipTrackingUrl()
        {
            return $this->_getUrl('shiptracking/index');
        }
}
