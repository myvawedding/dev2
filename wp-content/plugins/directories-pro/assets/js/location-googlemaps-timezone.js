'use strict';

(function($) {
  DRTS.Location.api.getTimezone = function(latlng, callback, errorHandler) {
    $.get(DRTS_Location_googlemapsTimezoneEndpoint, {
      timestamp: Math.round(new Date().getTime() / 1000).toString(),
      latlng: latlng[0] + ',' + latlng[1]
    }, function(results) {
      console.log('GoogleMaps time zone results:', results);
      callback(results);
    }).fail(function() {
      var err = new Error('Failed fetching timezone for ' + latlng[0] + ',' + latlng[1]);
      if (errorHandler) {
        errorHandler(err);
      } else {
        throw err;
      }
    });
  };
})(jQuery);