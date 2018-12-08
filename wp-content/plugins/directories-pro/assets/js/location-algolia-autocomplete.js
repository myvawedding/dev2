'use strict';

(function($) {
  DRTS.Location.api.autocomplete = function(selector, callback) {
    var $field = $(selector);
    if (!$field.length) return;

    var options = {
      appId: DRTS_Location_algoliaAutocomplete.appId,
      apiKey: DRTS_Location_algoliaAutocomplete.apiKey,
      aroundLatLngViaIP: false // disable the extra search/boost around the source IP
    };
    if (DRTS_Location_algoliaAutocomplete.country && DRTS_Location_algoliaAutocomplete.country instanceof Array) {
      options.countries = DRTS_Location_algoliaAutocomplete.country;
    }
    $field.each(function(index, field) {
      var _options = options;
      _options.container = field;
      var autocomplete = places(_options);
      autocomplete.on('change', function(e) {
        console.log('Algolia autocomplete results:', e.suggestion);
        var results = {
          latlng: [e.suggestion.latlng.lat, e.suggestion.latlng.lng],
          address: e.suggestion.value,
          street: e.suggestion.name,
          city: e.suggestion.city,
          province: e.suggestion.administrative,
          zip: e.suggestion.postcode,
          country: e.suggestion.country
        };
        callback(results);
      });
    });
  };

  DRTS.Location.api.getSuggestions = function(query, callback) {
    var options = {
      query: query,
      aroundLatLngViaIP: false // disable the extra search/boost around the source IP
    };
    if (DRTS_Location_algoliaAutocomplete.country && DRTS_Location_algoliaAutocomplete.country instanceof Array) {
      options.countries = DRTS_Location_algoliaAutocomplete.country.join(',').toLowerCase();
    }

    var places = algoliasearch.initPlaces(DRTS_Location_algoliaAutocomplete.appId, DRTS_Location_algoliaAutocomplete.apiKey);
    places.search(options, function(err, res) {
      if (err) throw err;

      console.log('Algolia places REST API results:', res);
      var results = [];
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = res.hits[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          var item = _step.value;

          results.push({
            text: item.locale_names.default[0],
            latlng: [item._geoloc.lat, item._geoloc.lng]
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
    });
  };
})(jQuery);