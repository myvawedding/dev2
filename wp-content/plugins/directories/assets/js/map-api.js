'use strict';

var _createClass = function() {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }
  return function(Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps);
    if (staticProps) defineProperties(Constructor, staticProps);
    return Constructor;
  };
}();

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

(function($) {
  DRTS.Map = DRTS.Map || {};
  DRTS.Map.api = {
    settings: {},
    getMap: function getMap(selector, options) {}
  };
  DRTS.Map.markerHeight = function(height) {
    var diagonal = height * Math.sqrt(2);
    var trim = (diagonal - height) / 2;
    return diagonal - trim;
  };
  DRTS.Map.enableDirections = function(map) {
    var container = map.getContainer().find('.drts-map-directions');
    if (!container.length) return;

    // Reset form
    container.find('.drts-map-directions-input').val('');

    if (DRTS.Location && DRTS.Location.api) {
      DRTS.Location.api.autocomplete('.drts-map-directions-input', function() {});
    }

    container.find('.drts-map-directions-trigger').on('click', function(e) {
      var markerIndex = void 0,
        destination = void 0,
        url = void 0;

      e.preventDefault();

      markerIndex = container.find('.drts-map-directions-destination').val();
      if (markerIndex === '') return;

      destination = map.getMarkerPosition(markerIndex);
      if (!destination) {
        console.log('Invalid destination');
        return;
      }

      url = 'https://www.google.com/maps/dir/?api=1' + '&origin=' + encodeURIComponent(container.find('.drts-map-directions-input').val()) + '&destination=' + encodeURIComponent(destination.join(',')) + '&travelmode=' + encodeURIComponent($(this).data('travel-mode') || 'driving');
      console.log(url);
      window.open().location.href = url;
    });
  };
  DRTS.Map.map = function() {
    function _class(container, options) {
      _classCallCheck(this, _class);

      if (container instanceof jQuery) {
        this.container = container;
        this.containerSelector = '#' + container.attr('id');
      } else {
        this.container = $(container);
        this.containerSelector = container;
      }
      this.options = options;
      this.options.infobox = typeof this.options.infobox === 'undefined' || this.options.infobox ? true : false;
      this.options.infobox_event = this.options.infobox_event || 'click';
      this.options.default_location = this.options.default_location || {
        lat: 40.69847,
        lng: -73.95144
      };
      this.options.default_zoom = parseInt(this.options.default_zoom, 10) || 10;
      this.options.scrollwheel = this.options.scrollwheel || false;
      this.markers = [];
      this.currentMarker = null;
      this.popover = null;
      this.$map = this.container.find('.drts-map-map').addClass('drts-popover-ignore-click').outerHeight(this.options.height);
    }

    _createClass(_class, [{
      key: 'getOptions',
      value: function getOptions() {
        return this.options;
      }
    }, {
      key: 'getContainer',
      value: function getContainer() {
        return this.container;
      }
    }, {
      key: 'getContainerSelector',
      value: function getContainerSelector() {
        return this.containerSelector;
      }
    }, {
      key: 'setMarkers',
      value: function setMarkers(markers) {
        for (var i in markers) {
          this.addMarker(markers[i]);
        }
        return this;
      }
    }, {
      key: 'clearMarkers',
      value: function clearMarkers() {
        this.markers = [];
        return this;
      }
    }, {
      key: 'addMarker',
      value: function addMarker(marker) {}
    }, {
      key: 'getMarker',
      value: function getMarker(index) {
        if (false === index in this.markers) return;

        return this.markers[index];
      }
    }, {
      key: 'getMarkerPosition',
      value: function getMarkerPosition(index) {}
    }, {
      key: 'clickMarker',
      value: function clickMarker(marker, triggered) {}
    }, {
      key: 'draw',
      value: function draw(options) {}
    }, {
      key: 'onResized',
      value: function onResized() {}
    }, {
      key: 'getZoom',
      value: function getZoom() {}
    }, {
      key: 'getSouthWest',
      value: function getSouthWest() {}
    }, {
      key: 'getNorthEast',
      value: function getNorthEast() {}
    }, {
      key: 'getPopover',
      value: function getPopover(pixel, markerHeight, content) {
        var _this = this;

        if (!this.options.infobox) return;

        if (!this.popover) {
          var popover = this.container.find('span.drts-map-popover');
          if (!popover.length) {
            popover = $('<span style="position:absolute;" class="drts-map-popover"></span>').prependTo(this.container.find('.drts-map-container'));
          }
          DRTS.popover(popover.removeClass('drts-popover-processed'), {
            html: true,
            template: '<div class="' + DRTS.bsPrefix + 'popover drts-map-popover ' + DRTS.bsPrefix + 'p-0" style="width:' + (this.options.infobox_width || 240) + 'px">' + '<div class="' + DRTS.bsPrefix + 'arrow"></div><div class="' + DRTS.bsPrefix + 'close ' + DRTS.bsPrefix + 'p-1 drts-map-popover-close" aria-label="Close"><i aria-hidden="true" class="fas fa-times"></i></div>' + '<div class="' + DRTS.bsPrefix + 'popover-body ' + DRTS.bsPrefix + 'p-0"></div></div>',
            placement: 'top',
            container: this.container
          });
          popover.on('hidden.bs.popover', function() {
            _this.currentMarker = null;
          });
          this.popover = popover;
        }
        if (pixel) {
          var _top = pixel.y;
          if (markerHeight) {
            _top -= markerHeight;
          }
          this.popover.css({
            left: pixel.x + 'px',
            top: _top + 'px'
          });
        }
        if (content) {
          this.popover.attr('data-content', content);
        }
        return this.popover;
      }
    }]);

    return _class;
  }();
})(jQuery);