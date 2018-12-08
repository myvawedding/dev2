'use strict';

(function($) {
  DRTS.Location.api.autocomplete = function(selector, callback) {
    var $field = $(selector);
    if (!$field.length) return;

    var options = {
      types: [DRTS_Location_googlemapsAutocomplete.type || '(regions)'],
      componentRestrictions: {}
    };
    if (DRTS_Location_googlemapsAutocomplete.country && DRTS_Location_googlemapsAutocomplete.country instanceof Array) {
      options.componentRestrictions.country = DRTS_Location_googlemapsAutocomplete.country;
    }
    $field.each(function(index, field) {
      google.maps.event.addDomListener(field, 'focus', function(e) {
        var autocomplete = new google.maps.places.Autocomplete(field, options);
        autocomplete.addListener('place_changed', function() {
          var place = autocomplete.getPlace();
          console.log('GoogleMaps autocomplete results:', place);
          callback(DRTS.Location.googlemaps.parsePlace(place));
        });
      });
      google.maps.event.addDomListener(field, 'keydown', function(e) {
        if (e.keyCode === 13) {
          e.preventDefault();
        }
      });
    });
  };

  DRTS.Location.api.getSuggestions = function(query, callback) {
    var autocomplete = new google.maps.places.AutocompleteService();
    var options = {
      input: query,
      types: [DRTS_Location_googlemapsAutocomplete.type || '(regions)'],
      componentRestrictions: {}
    };
    if (DRTS_Location_googlemapsAutocomplete.country) {
      options.componentRestrictions.country = DRTS_Location_googlemapsAutocomplete.country;
    }
    autocomplete.getPlacePredictions(options, function(predictions, status) {
      if (status === google.maps.places.PlacesServiceStatus.OK) {
        console.log('GoogleMaps place predictions:', predictions);
        var results = [];
        var _iteratorNormalCompletion = true;
        var _didIteratorError = false;
        var _iteratorError = undefined;

        try {
          for (var _iterator = predictions[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
            var item = _step.value;

            results.push({
              text: item.description
            });
          }
        } catch (err) {
          _didIteratorError = true;
          _iteratorError = err;
        } finally {
          try {
            if (!_iteratorNormalCompletion && _iterator.return) {
              _iterator.return();
            }
          } finally {
            if (_didIteratorError) {
              throw _iteratorError;
            }
          }
        }

        callback(results);
      }
    });
  };
})(jQuery);