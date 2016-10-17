// This example displays an address form, using the autocomplete feature
// of the Google Places API to help users fill in the information.
// for shipping
"use strict";
var SHIPITAUTOCOMPLETE_SHIPPING = SHIPITAUTOCOMPLETE_SHIPPING || {};

SHIPITAUTOCOMPLETE_SHIPPING.event = {};
SHIPITAUTOCOMPLETE_SHIPPING.method = {
    countryCode: "",
    placeSearch: "", //
    IdSeparator: "", //
    autocomplete : "",
    autocompleteListener : "",
    streetNumber : "",
    formFields : {
        'street1': '',
        'street2': '',
        'city': '',
        //'region': '',
        'postcode': '',
        'region_id' : ''
    },
    formFieldsValue : {
        'street1': '',
        'street2': '',
        'city': '',
        //'region': '',
        'postcode': '',
        'region_id' : ''
    },
    component_form : "",
    useShipItApi : function() {
      jQuery('#shipping\\:street1').off('blur');
      jQuery("#shipping\\:street1").autocomplete({
        autoFocus: true,
        source: function( request, response ) {
          jQuery.ajax({
            url: "https://api.shipit.click/location?integration=magento&format=json",
            dataType: "json",
            data: {
              apiKey: "shipit-apikey",
              light: "true",
              address: request.term
            },
            success: function(data) {
              response(jQuery.map(data.addresses, function(item) {
                return {
                          addressId: item.address_id,
                          label: item.full_address,
                          value: item.full_address
                        };
              }));
            }
          });
        },
        minLength: 3,
        delay: 0,
        select: function(event, ui) {
          // When the user selects an address from the dropdown,
          // populate the address fields in the form.
          SHIPITAUTOCOMPLETE_SHIPPING.method.fillInAddressShipIt(ui.item.addressId);
        } 
      });
      if (this.autocomplete !== "") {
        google.maps.event.removeListener(this.autocompleteListener);
        google.maps.event.clearInstanceListeners(this.autocomplete);
        jQuery(".pac-container").remove();
        this.autocomplete = "";
      }
    },
    useGoogleMapApi : function() {
      jQuery('#shipping\\:street1').off('blur');
      if (this.autocomplete === "") {
        try {
          jQuery('#shipping\\:street1').autocomplete("destroy");
        }
        catch (err) {
        }
        this.autocomplete = new google.maps.places.Autocomplete((document.getElementById('shipping:street1')), { types: ['geocode']});
            
        // When the user selects an address from the dropdown,
        // populate the address fields in the form.
        this.autocompleteListener = google.maps.event.addListener(this.autocomplete, 'place_changed', function( event ) {SHIPITAUTOCOMPLETE_SHIPPING.method.fillInAddress()});
      }
    },
    initialize: function(){
        //init form
        this.getIdSeparator();
        this.initFormFields();
        if (this.countryCode == 'NZ') {
          //New Zealand
          SHIPITAUTOCOMPLETE_SHIPPING.method.useShipItApi();
        }
        else {
          //Rest of the World
          SHIPITAUTOCOMPLETE_SHIPPING.method.useGoogleMapApi();
        }
        
        var shipping_address = document.getElementById("shipping:street1");
          if(shipping_address != null){
  			 	  shipping_address.addEventListener("focus", function( event ) {SHIPITAUTOCOMPLETE_SHIPPING.method.setAutocompleteCountry()}, true);
  			} 
  
        var shipping_country = document.getElementById("shipping:country_id");
        if(shipping_country != null){
        	 shipping_country.addEventListener("change", function( event ) {SHIPITAUTOCOMPLETE_SHIPPING.method.setAutocompleteCountry()}, true);
        }
    },
    getIdSeparator : function() {
        if (!document.getElementById('shipping:street1')) {
           this.IdSeparator = "_";
            return "_";
        }
        this.IdSeparator = ":";
        return ":";
    },
    initFormFields: function ()
    {
        for (var field in this.formFields) {
            this.formFields[field] = ('shipping' + this.IdSeparator + field);
        }
        this.component_form =
        {
            //'administrative_area_level_3': ['street1', 'long_name'],
            //'neighborhood': ['street1', 'long_name'],
            //'subpremise': ['street1', 'short_name'],
            'street_number': ['street1', 'short_name'],
            'route': ['street1', 'long_name'],
            //'sublocality': ['street2', 'long_name'],
            //sublocality_level_1': ['street2', 'long_name'],
            'locality': ['city', 'long_name'],
            //'administrative_area_level_1': [formFields.region, 'long_name'],
            'administrative_area_level_1': ['region_id', 'long_name'],
            'postal_code': ['postcode', 'short_name']
        };
    },
    fillInAddress : function () {
        // [START region_fillform]
        this.clearFormValues();
        // Get the place details from the autocomplete object.
        var place = this.autocomplete.getPlace();
        this.resetForm();
        var type = '';
        for (var field in place.address_components) {
            for (var t in  place.address_components[field].types)
            {
                for (var f in this.component_form) {
                    var types = place.address_components[field].types;
                    if(f == types[t])
                    {
                        if(f == "street_number")
                        {
                            this.streetNumber = place.address_components[field]['short_name'];
                        }

                        var prop = this.component_form[f][1];
                        if(place.address_components[field].hasOwnProperty(prop)){
                            this.formFieldsValue[this.component_form[f][0]] = place.address_components[field][prop];
                        }

                    }
                }
            }
        }

        this.appendStreetNumber();
        this.fillForm();
    },
    fillInAddressShipIt : function (addressId)
    {     
      jQuery.post("https://api.shipit.click/location?integration=magento&format=json",
        {
					apiKey: "shipit-apikey",
					light: "true",
					address: "",
					addressId: addressId
				},
				function (response) {
          if (response.address.address1 != "") {
            setTimeout(function() {
              jQuery('#shipping\\:street1')
                .val(response.address.address1 + ", " + response.address.address2)
                .blur(function(e) {
                    this.value = response.address.address1 + ", " + response.address.address2;
                });
            },50);           
          }
          else {
            setTimeout(function() {
              jQuery('#shipping\\:street1')
                .val(response.address.address2)
                .blur(function(e) {
                    this.value = response.address.address2;
                });
            },50);
          }
          jQuery('#shipping\\:street2').val(response.address.address3);
          jQuery('#shipping\\:city').val(response.address.city);
          jQuery('#shipping\\:region').val(response.address.province);
          jQuery('#shipping\\:postcode').val(response.address.postal_code);
				},
				"json");
    },
    clearFormValues: function ()
    {
        for (var f in this.formFieldsValue) {
            this.formFieldsValue[f] = '';
        }
    },
    appendStreetNumber : function ()
    {
        if(this.streetNumber != '')
        {
            this.formFieldsValue['street1'] =  this.streetNumber + ' '
            + this.formFieldsValue['street1'];
        }
    },
    fillForm : function()
    {
        for (var f in this.formFieldsValue) {
            if(f == 'region_id' )
            {
                this.selectRegion( f,this.formFieldsValue[f]);
            }
            else
            {
            	 if(document.getElementById(('shipping' + this.IdSeparator + f)) === null){
							   continue;
							 }
							 else
							 {
							 		document.getElementById(('shipping' + this.IdSeparator + f)).value = this.formFieldsValue[f];
							 }
              
            }
        } 
    },
    selectRegion:function (id,regionText)
    {
    	 if(document.getElementById(('shipping' + this.IdSeparator + id)) == null){
			   return false;
			 } 
        var el = document.getElementById(('shipping' + this.IdSeparator + id));
        for(var i=0; i<el.options.length; i++) {
            if ( el.options[i].text == regionText ) {
                el.selectedIndex = i;
                break;
            }
        }
    },
    resetForm :function ()
    {
    	 if(document.getElementById(('shipping' + this.IdSeparator + 'street2')) !== null){
			   document.getElementById(('shipping' + this.IdSeparator + 'street2')).value = '';
			 }   
    },
    setAutocompleteCountry : function ()
    {
      if(document.getElementById('shipping:country_id') === null) {
        country = 'NZ'; //change your codes for default country
        this.countryCode = 'NZ'; 
      }
			else {
        var country = document.getElementById('shipping:country_id').value;
        this.countryCode = country;
      }
      if (this.countryCode == 'NZ') {
        SHIPITAUTOCOMPLETE_SHIPPING.method.useShipItApi();
      }
      else {
        SHIPITAUTOCOMPLETE_SHIPPING.method.useGoogleMapApi();
        this.autocomplete.setComponentRestrictions({ 'country': country });
      }
    }
}

window.addEventListener('load', function(){ SHIPITAUTOCOMPLETE_SHIPPING.method.initialize() });
