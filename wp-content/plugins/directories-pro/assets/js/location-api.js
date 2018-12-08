'use strict';

(function($) {
  DRTS.Location = DRTS.Location || {};
  DRTS.Location.api = {
    settings: {},
    geocode: function geocode(address, callback, errorHandler) {
      console.log('DRTS.Location.api.geocode() is not implemented.');
    },
    reverseGeocode: function reverseGeocode(latlng, callback, errorHandler) {
      console.log('DRTS.Location.api.reverseGeocode() is not implemented.');
    },
    getTimezone: function getTimezone(latlng, callback, errorHandler) {
      console.log('DRTS.Location.api.getTimezone() is not implemented.');
    },
    autocomplete: function autocomplete(selector, callback) {
      console.log('DRTS.Location.api.autocomplete() is not implemented.');
    },
    getSuggestions: function getSuggestions(query, callback) {
      console.log('DRTS.Location.api.getSuggestions() is not implemented.');
    }
  };
})(jQuery);