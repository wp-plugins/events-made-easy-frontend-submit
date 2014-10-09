var emefs_page = 0;
var emefs_gmap_enabled = 1;
var emefs_gmap_hasSelectedLocation = 0;

function htmlDecode(value){
	return jQuery('<div/>').html(value).text(); 
}

function emefs_deploy(emefs_autocomplete_url,show24Hours) {

        jQuery("input#location_name").autocomplete({
            source: function(request, response) {
                         jQuery.ajax({ url: emefs_autocomplete_url,
                                  data: { q: request.term},
                                  dataType: "json",
                                  type: "GET",
                                  success: function(data){
                                                response(jQuery.map(data, function(item) {
                                                      return {
                                                         label: item.name,
                                                         name: htmlDecode(item.name),
                                                         address: item.address,
                                                         town: item.town,
                                                         latitude: item.latitude,
                                                         longitude: item.longitude,
                                                      };
                                                }));
                                           }
                                 });
                    },
            select:function(evt, ui) {
                         // when a product is selected, populate related fields in this form
                         jQuery('input#location_name').val(ui.item.name);
                         jQuery('input#location_address').val(ui.item.address);
                         jQuery('input#location_town').val(ui.item.town);
                         jQuery('input#location_latitude').val(ui.item.latitude);
                         jQuery('input#location_longitude').val(ui.item.longitude);
                         emefs_displayAddress();
                         return false;
                   },
            minLength: 1
        }).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
            return jQuery( "<li></li>" )
            .append("<a><strong>"+htmlDecode(item.name)+'</strong><br /><small>'+htmlDecode(item.address)+' - '+htmlDecode(item.town)+ '</small></a>')
            .appendTo( ul );
        };

	
   jQuery("#localised-start-date").show();
   jQuery("#localised-end-date").show();
   jQuery("#event_start_date").hide();
   jQuery("#event_end_date").hide();

   locale_code=emefs.locale;
   firstDay=emefs.firstDayOfWeek;
   jQuery.datepick.setDefaults( jQuery.datepick.regionalOptions[locale_code] );
   jQuery.datepick.setDefaults({
      changeMonth: true,
      changeYear: true,
      altFormat: "yyyy-mm-dd",
      firstDay: firstDay
   });
   jQuery("#localised-start-date").datepick({ altField: "#event_start_date" });
   jQuery("#localised-end-date").datepick({ altField: "#event_end_date" });

   jQuery('#event_start_time, #event_end_time').timeEntry({ hourText: 'Hour', minuteText: 'Minute', show24Hours: show24Hours, spinnerImage: '' });
	
	if(emefs_gmap_hasSelectedLocation){
		emefs_displayAddress(1);
	}

   jQuery("input#location_name").change(function(){
         emefs_displayAddress(0);
   });
   jQuery("input#location_town").change(function(){
         emefs_displayAddress(1);
   });
   jQuery("input#location_address").change(function(){
         emefs_displayAddress(1);
   });
   jQuery("input#location_latitude").change(function(){
         emefs_displayAddress(0);
   });
   jQuery("input#location_longitude").change(function(){
         emefs_displayAddress(0);
   });

}

function emefs_displayAddress(ignore_coord){
	if(emefs_gmap_enabled) {
	   eventLocation = jQuery("input#location_name").val(); 
	   eventTown = jQuery("input#location_town").val(); 
	   eventAddress = jQuery("input#location_address").val();
      if (ignore_coord) {
         emefs_loadMapLatLong(eventLocation, eventTown, eventAddress);
      } else {
         eventLat = jQuery("input#location_latitude").val();
         eventLong = jQuery("input#location_longitude").val();
	      emefs_loadMapLatLong(eventLocation, eventTown, eventAddress, eventLat, eventLong);
      }
	}
}

function emefs_loadMap(location, town, address){

	var emefs_mapCenter = new google.maps.LatLng(-34.397, 150.644);
	var emefs_map = false;
	
	var emefs_mapOptions = {
		zoom: 12,
		center: emefs_mapCenter,
		scrollwheel: true,
		disableDoubleClickZoom: true,
		mapTypeControlOptions: {
			mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE]
		},
		mapTypeId: google.maps.MapTypeId.ROADMAP
	}

	var emefs_geocoder = new google.maps.Geocoder();
	
	if (address !="") {
		searchKey = address + ", " + town;
	} else {
		searchKey =  location + ", " + town;
	}
	
	emefs_geocoder.geocode({'address': searchKey}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			jQuery("#event-map").slideDown('fast',function(){
				if(!emefs_map){
					var emefs_map = new google.maps.Map(document.getElementById("event-map"), emefs_mapOptions);
				}
				emefs_map.setCenter(results[0].geometry.location);
				var emefs_marker = new google.maps.Marker({
					map: emefs_map,
					position: results[0].geometry.location
				});
				var emefs_infowindow = new google.maps.InfoWindow({
					content: '<strong>' + location +'</strong><p>' + address + '</p><p>' + town + '</p>'
	         });
				emefs_infowindow.open(emefs_map,emefs_marker);
				jQuery('input#location_latitude').val(results[0].geometry.location.lat());
				jQuery('input#location_longitude').val(results[0].geometry.location.lng());
			});
		} else {
			jQuery("#event-map").slideUp();
		}
	});
	
}

function emefs_loadMapLatLong(location, town, address, lat, long) {
   if (lat === undefined) {
      lat = 0;
   }
   if (long === undefined) {
      long = 0;
   }

   if (lat != 0 && long != 0) {
      var latlng = new google.maps.LatLng(lat, long);
      var myOptions = {
		   zoom: 12,
		   center: latlng,
		   scrollwheel: true,
		   disableDoubleClickZoom: true,
         mapTypeControlOptions: {
            mapTypeIds:[google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE]
         },
         mapTypeId: google.maps.MapTypeId.ROADMAP
      }
		jQuery("#event-map").slideDown('fast',function(){
         var emefs_map = new google.maps.Map(document.getElementById("event-map"), myOptions);
         var emefs_marker = new google.maps.Marker({
            map: emefs_map,
            position: latlng
         });
         var emefs_infowindow = new google.maps.InfoWindow({
            content: '<div class=\"eme-location-balloon\"><strong>' + location +'</strong><p>' + address + '</p><p>' + town + '</p></div>'
         });
         emefs_infowindow.open(emefs_map,emefs_marker);
      });
   } else {
      emefs_loadMap(location, town, address);
   }
}

