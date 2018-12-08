'use strict';

(function($) {
  DRTS.Location.api.getTimezone = function(latlng, callback, errorHandler) {
    $.get('https://maps.googleapis.com/maps/api/timezone/json', {
      timestamp: Math.round(new Date().getTime() / 1000).toString(),
      location: latlng[0] + ',' + latlng[1],
      key: DRTS_Location_googlemapsTimezoneApiKey
    }, function(results) {
      console.log('GoogleMaps time zone results:', results);
      if (results.status === 'OK') {
        callback(results.timeZoneId);
      } else {
        var err = new Error('Failed fetching timezone for ' + latlng[0] + ',' + latlng[1]);
        if (errorHandler) {
          errorHandler(err);
        } else {
          throw err;
        }
      }
    });
  };
})(jQuery);