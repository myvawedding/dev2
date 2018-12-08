'use strict';

(function($) {
  DRTS.Location.api.geocode = function(address, callback, errorHandler) {
    var geocoder = new google.maps.Geocoder();
    var request = {
      address: address,
      componentRestrictions: {}
    };
    if (DRTS_Location_googlemapsGeocoding.country && DRTS_Location_googlemapsGeocoding.country instanceof Array) {
      // Only single country seems to be supported. Should we request multiple times?
      request.componentRestrictions.country = DRTS_Location_googlemapsGeocoding.country[0];
    }
    geocoder.geocode(request, function(results, status) {
      if (status === google.maps.GeocoderStatus.OK) {
        console.log('GoogleMaps geocoding results:', results);
        var latlng = results[0].geometry.location;
        callback([latlng.lat(), latlng.lng()]);
      } else {
        var err = new Error('Geocoder failed due to: ' + status);
        if (errorHandler) {
          errorHandler(err);
        } else {
          throw err;
        }
      }
    });
  };

  DRTS.Location.api.reverseGeocode = function(latlng, callback, errorHandler) {
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      'latLng': new google.maps.LatLng(latlng[0], latlng[1])
    }, function(results, status) {
      if (status !== google.maps.GeocoderStatus.OK) {
        throw new Error('Geocoder failed due to: ' + status);
      }
      if (!results[0] || !results[0].address_components) {
        var err = new Error('Geocoder returned no address components');
        if (errorHandler) {
          errorHandler(err);
          return;
        } else {
          throw err;
        }
      }

      console.log('GoogleMaps reverse geocoding results:', results);
      callback(DRTS.Location.googlemaps.parsePlace(results[0]));
    });
  };
})(jQuery);