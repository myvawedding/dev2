'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function(obj) {
  return typeof obj;
} : function(obj) {
  return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
};

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

var _get = function get(object, property, receiver) {
  if (object === null) object = Function.prototype;
  var desc = Object.getOwnPropertyDescriptor(object, property);
  if (desc === undefined) {
    var parent = Object.getPrototypeOf(object);
    if (parent === null) {
      return undefined;
    } else {
      return get(parent, property, receiver);
    }
  } else if ("value" in desc) {
    return desc.value;
  } else {
    var getter = desc.get;
    if (getter === undefined) {
      return undefined;
    }
    return getter.call(receiver);
  }
};

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

function _possibleConstructorReturn(self, call) {
  if (!self) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }
  return call && (typeof call === "object" || typeof call === "function") ? call : self;
}

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
  }
  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      enumerable: false,
      writable: true,
      configurable: true
    }
  });
  if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
}

(function($) {
  DRTS.Map.googlemaps = {
    styles: {}
  };

  DRTS.Map.googlemaps.map = function(_DRTS$Map$map) {
    _inherits(_class, _DRTS$Map$map);

    function _class(container, options) {
      _classCallCheck(this, _class);

      var _this = _possibleConstructorReturn(this, (_class.__proto__ || Object.getPrototypeOf(_class)).call(this, container, options));

      _this.markerClusterer = null;
      _this.overlay = null;
      _this.currentCircle = null;
      var mapTypeIds = [];
      for (var mapType in google.maps.MapTypeId) {
        mapTypeIds.push(google.maps.MapTypeId[mapType]);
      }
      var settings = {
        mapTypeId: $.inArray(_this.options.type, mapTypeIds) !== -1 ? _this.options.type : google.maps.MapTypeId.ROADMAP,
        mapTypeControl: typeof _this.options.map_type_control === 'undefined' || _this.options.map_type_control ? true : false,
        zoomControl: true,
        streetViewControl: false,
        scaleControl: false,
        rotateControl: false,
        fullscreenControl: _this.options.fullscreen_control || false,
        center: new google.maps.LatLng(_this.options.default_location.lat, _this.options.default_location.lng),
        scrollwheel: _this.options.scrollwheel,
        styles: _this.options.style && DRTS.Map.googlemaps.styles[_this.options.style] ? DRTS.Map.googlemaps.styles[_this.options.style] : [{
          'featureType': 'poi',
          'stylers': [{
            'visibility': 'off'
          }]
        }],
        zoom: _this.options.default_zoom
      };
      if (settings.mapTypeControl) {
        settings.mapTypeControlOptions = {
          style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
          mapTypeIds: mapTypeIds,
          position: google.maps.ControlPosition.TOP_RIGHT
        };
      }
      _this.map = new google.maps.Map(_this.$map.get(0), settings);

      // Add marker clusterer?
      if (_this.options.marker_clusters) {
        var marker_clusterer_options = {
          maxZoom: 15
        };
        if (_this.options.marker_cluster_imgurl) {
          marker_clusterer_options.imagePath = _this.options.marker_cluster_imgurl + '/m';
        }
        _this.markerClusterer = new MarkerClusterer(_this.map, [], marker_clusterer_options);
      }

      // Enable popover
      if (_this.options.infobox) {
        _this.getPopover();
        _this.getOverlay();
        google.maps.event.addListener(_this.map, 'dragstart', function() {
          _this.getPopover().sabaiPopover('hide');
          _this.currentMarker = null;
        });
        google.maps.event.addListener(_this.map, 'zoom_changed', function() {
          _this.getPopover().sabaiPopover('hide');
          _this.currentMarker = null;
        });
      }

      // Init street view panorama
      _this.map.getStreetView().setOptions({
        disableDefaultUI: true,
        enableCloseButton: false,
        zoomControl: true,
        visible: false
      });

      // Fire events
      google.maps.event.addListener(_this.map, 'click', function(e) {
        _this.container.trigger('map_clicked.sabai', {
          latlng: [e.latLng.lat(), e.latLng.lng()]
        });
      });
      google.maps.event.addListener(_this.map, 'zoom_changed', function(e) {
        _this.container.trigger('map_zoom_changed.sabai', {
          zoom: _this.map.getZoom()
        });
      });
      return _this;
    }

    _createClass(_class, [{
      key: 'clearMarkers',
      value: function clearMarkers() {
        for (var i in this.markers) {
          this.markers[i].setMap(null);
        }
        if (this.markerClusterer) {
          this.markerClusterer.clearMarkers();
        }
        return _get(_class.prototype.__proto__ || Object.getPrototypeOf(_class.prototype), 'clearMarkers', this).call(this);
      }
    }, {
      key: 'addMarker',
      value: function addMarker(marker) {
        var _marker = void 0;
        var defaultMarkerIconOptions = void 0;
        if (this.options.marker_custom) {
          if (marker.icon) {
            var markerIconOptions = {
              html: marker.icon.url ? $('<img/>').attr('src', marker.icon.url)[0].outerHTML : null,
              icon: marker.icon.icon || this.options.marker_icon,
              icon_color: marker.icon.icon_color || this.options.marker_icon_color,
              size: marker.icon.size || this.options.marker_size,
              color: marker.icon.color || this.options.marker_color || '#fff',
              event: this.options.infobox_event
            };
            _marker = new DRTS.Map.googlemaps.map.marker(markerIconOptions);
          } else {
            if (typeof defaultMarkerIconOptions === 'undefined') {
              defaultMarkerIconOptions = {
                icon: this.options.marker_icon || 'fas fa-dot-circle',
                icon_color: this.options.marker_icon_color,
                size: this.options.marker_size,
                color: this.options.marker_color || '#fff',
                event: this.options.infobox_event
              };
            }
            _marker = new DRTS.Map.googlemaps.map.marker(defaultMarkerIconOptions);
          }
        } else {
          _marker = new google.maps.Marker();
        }
        _marker.setPosition(new google.maps.LatLng(marker.lat, marker.lng));
        _marker.set('id', marker.entity_id + '-' + marker.index);
        _marker.set('content', marker.content);
        _marker.set('entity_id', marker.entity_id);

        this.markers[_marker.get('id')] = _marker;
        return this;
      }
    }, {
      key: 'getMarkerPosition',
      value: function getMarkerPosition(index) {
        if (typeof index === 'undefined' || index === null) {
          index = Object.keys(this.markers)[0];
        }
        if (false === index in this.markers) return;

        var pos = this.markers[index].getPosition();
        return [pos.lat(), pos.lng()];
      }
    }, {
      key: 'draw',
      value: function draw(options) {
        options = options || {};
        this.currentMarker = null;
        if (this.currentCircle) {
          this.currentCircle.setMap(null);
        }

        if (Object.keys(this.markers).length > 0) {
          var fit_bounds = void 0,
            bounds = void 0;
          fit_bounds = typeof options.fit_bounds === 'undefined' ? this.options.fit_bounds : options.fit_bounds;

          if (fit_bounds && Object.keys(this.markers).length > 1) {
            bounds = new google.maps.LatLngBounds();
          }

          for (var i in this.markers) {
            if (!this.markerClusterer) {
              // will add markers in bulk later if marker cluster exists
              this.markers[i].setMap(this.map);
            }
            if (bounds) {
              var pos = this.markers[i].getPosition();
              bounds.extend(pos);
              if (options.center) {
                // Extend bound to include the point opposite the marker so the center stays the same
                bounds.extend(new google.maps.LatLng(options.center[0] * 2 - pos.lat(), options.center[1] * 2 - pos.lng()));
              }
            }
            google.maps.event.addListener(this.markers[i], this.options.infobox_event, function(map, marker) {
              return function(e) {
                map.clickMarker(marker);
              };
            }(this, this.markers[i]));

            if (Object.keys(this.markers).length <= 100) {
              // Bounce on display
              this.markers[i].setAnimation(google.maps.Animation.BOUNCE);
              setTimeout(function(marker) {
                return function() {
                  marker.setAnimation(null);
                };
              }(this.markers[i]), 500);
            }
          }

          if (this.markerClusterer) {
            this.markerClusterer.addMarkers(Object.values(this.markers));
          }

          if (bounds) {
            this.map.fitBounds(bounds);
          } else {
            // Center position required if no automatic bounding
            if (!options.center) {
              var _pos = this.markers[Object.keys(this.markers)[0]].getPosition();
              options.center = [_pos.lat(), _pos.lng()];
            }
          }

          if (options.street_view) {
            this.drawStreetView(_typeof(options.street_view) === 'object' ? options.street_view : this.markers[Object.keys(this.markers)[0]]);
          }
        }

        if (options.center) {
          var center = new google.maps.LatLng(options.center[0], options.center[1]);
          this.map.setZoom(options.zoom || this.options.default_zoom || 10);
          this.map.panTo(center);
          if (options.circle) {
            this.currentCircle = new google.maps.Circle({
              strokeColor: options.circle.stroke_color || '#99f',
              strokeOpacity: 0.8,
              strokeWeight: 1,
              fillColor: options.circle.fill_color || '#99f',
              fillOpacity: 0.3,
              map: this.map,
              center: center,
              radius: options.circle.radius
            });
          }
        }

        $(DRTS).trigger('map_drawn.sabai', {
          map: this
        });

        return this;
      }
    }, {
      key: 'clickMarker',
      value: function clickMarker(marker, triggered) {
        if (this.currentMarker && this.currentMarker.get('id') === marker.get('id')) {
          this.showMarkerContent(marker, triggered);
          if (!triggered) {
            // make sure manually clicked
            this.container.trigger('marker_click.sabai', {
              map: this,
              marker: marker
            });
          }
          return;
        }

        if (triggered && this.markerClusterer) {
          // Add back previously removed marker
          if (this.currentMarker) {
            this.markerClusterer.addMarker(this.currentMarker);
          }
          // Remove marker from cluster for better view of the marker
          this.markerClusterer.removeMarker(marker);
          marker.setMap(this.map);
        }

        if (this.map.getBounds() && !this.map.getBounds().contains(marker.getPosition())) {
          this.map.panTo(marker.getPosition());
        }

        if (this.currentMarker) {
          //current.setAnimation(null);
          this.currentMarker.setZIndex(0);
        }
        marker.setZIndex(1);

        this.showMarkerContent(marker, triggered);

        this.currentMarker = marker;

        if (!triggered) {
          // make sure manually clicked
          this.container.trigger('marker_click.sabai', {
            map: this,
            marker: marker
          });
        }
      }
    }, {
      key: 'showMarkerContent',
      value: function showMarkerContent(marker, triggered) {
        var popover = this.getPopover();

        // Close if popover is currently open
        if (popover) popover.sabaiPopover('hide');

        // Animate marker if triggered, or manually clicked and no infobox
        if (triggered || !this.options.infobox) {
          marker.setAnimation(google.maps.Animation.BOUNCE);
          setTimeout(function() {
            marker.setAnimation(null);
          }, 1000);
        }

        if (triggered && !this.options.trigger_infobox || // trigger infobox disabled
          !popover // No overlay or is not ready
          ||
          !this.getOverlay() || !this.getOverlay().getProjection() || !marker.get('content')) {
          return;
        }

        this.getPopover(this.getOverlay().getProjection().fromLatLngToContainerPixel(marker.getPosition()), marker.get('marker_height') || 38, marker.get('content')).sabaiPopover('show');
      }
    }, {
      key: 'onResized',
      value: function onResized() {
        this.getOverlay(true);
        google.maps.event.trigger(this.map, 'resize');
        return this;
      }
    }, {
      key: 'getZoom',
      value: function getZoom() {
        return this.map.getZoom();
      }
    }, {
      key: 'getSouthWest',
      value: function getSouthWest() {
        var bounds = this.map.getBounds();
        return [bounds.getSouthWest().lat(), bounds.getSouthWest().lng()];
      }
    }, {
      key: 'getNorthEast',
      value: function getNorthEast() {
        var bounds = this.map.getBounds();
        return [bounds.getNorthEast().lat(), bounds.getNorthEast().lng()];
      }
    }, {
      key: 'getOverlay',
      value: function getOverlay(create) {
        if (!this.overlay || create) {
          this.overlay = new google.maps.OverlayView();
          this.overlay.draw = function() {};
          this.overlay.setMap(this.map);
        }
        return this.overlay;
      }
    }, {
      key: 'drawStreetView',
      value: function drawStreetView(position, radius, notify) {
        var sv = new google.maps.StreetViewService(),
          map = this.map,
          marker = void 0;
        if (position.setMap) {
          marker = position;
          position = position.getPosition();
        }
        sv.getPanorama({
          location: position,
          radius: radius || 50
        }, function(data, status) {
          if (status === google.maps.StreetViewStatus.OK) {
            var pano = map.getStreetView();
            pano.setPosition(data.location.latLng);
            if (marker) {
              var heading = google.maps.geometry.spherical.computeHeading(data.location.latLng, position);
              pano.setPov({
                heading: heading,
                pitch: 0,
                zoom: 1
              });
              marker.setMap(pano);
            }
            pano.setVisible(true);
          } else {
            if (notify) {
              alert('No street map view is available for this location.');
            }
            console.log(status);
          }
        });
        return this;
      }
    }]);

    return _class;
  }(DRTS.Map.map);

  DRTS.Map.api.getMap = function(container, options) {
    return new DRTS.Map.googlemaps.map(container, options);
  };

  DRTS.Map.googlemaps.map.marker = function(options) {
    this.options = options || {};
    this.visible = true;
    this.classes = ['drts-map-marker'];
    this.div = null;
  };
  DRTS.Map.googlemaps.map.marker.prototype = new google.maps.OverlayView();
  DRTS.Map.googlemaps.map.marker.prototype.onAdd = function() {
    var _this2 = this;

    var size = this.options.size || 38,
      marker = void 0;
    this.div = document.createElement('div');
    this.div.className = this.classes.join(' ');
    this.div.style.width = size + 'px';
    this.div.style.height = size + 'px';
    this.div.style.marginTop = '-' + (size * Math.sqrt(2) - DRTS.Map.markerHeight(size)) + 'px';
    marker = document.createElement('div');
    if (this.options.color) {
      this.div.style.backgroundColor = this.div.style.color = marker.style.borderColor = this.options.color;
    }
    if (this.options.data) {
      this.div.dataset = this.options.data;
    }
    if (this.options.html) {
      marker.innerHTML = this.options.html;
      //this.div.innerHTML = '<div style="border-color:' + this.options.color + ';">' + this.options.html + '</div>';
    } else if (this.options.icon) {
      marker.innerHTML = '<i class="' + this.options.icon + '"></i>';
      if (this.options.icon_color) marker.style.backgroundColor = this.options.icon_color;
      //this.div.innerHTML = '<div style="border-color:' + this.options.color + ';">'+ '<i class="' + this.options.icon + '" style="'
      //    + (this.options.icon_color ? 'color:' + this.options.icon_color : '') + ';"></i></div>';
    }
    this.div.appendChild(marker);
    this.getPanes().overlayImage.appendChild(this.div);
    var ev = this.options.event;
    google.maps.event.addDomListener(this.div, ev, function(event) {
      google.maps.event.trigger(_this2, ev);
    });
    this.setPosition(this.position);
    this.set('marker_height', DRTS.Map.markerHeight(size));
  };
  DRTS.Map.googlemaps.map.marker.prototype.draw = function() {
    this.setPosition(this.position);
  };
  DRTS.Map.googlemaps.map.marker.prototype.setPosition = function(position) {
    this.position = position;
    if (this.div) {
      var point = this.getProjection().fromLatLngToDivPixel(this.position);
      if (point) {
        this.div.style.left = point.x + 'px';
        this.div.style.top = point.y + 'px';
      }
    }
  };
  DRTS.Map.googlemaps.map.marker.prototype.onRemove = function() {
    if (this.div) {
      this.div.parentNode.removeChild(this.div);
    }
    this.div = null;
  };
  DRTS.Map.googlemaps.map.marker.prototype.getPosition = function() {
    return this.position;
  };
  DRTS.Map.googlemaps.map.marker.prototype.setDraggable = function(draggable) {
    this.draggable = draggable;
  };
  DRTS.Map.googlemaps.map.marker.prototype.getDraggable = function() {
    this.draggable;
  };
  DRTS.Map.googlemaps.map.marker.prototype.getVisible = function() {
    return this.visible;
  };
  DRTS.Map.googlemaps.map.marker.prototype.setVisible = function(visible) {
    if (this.div) {
      this.div.style.display = visible ? 'inline-block' : 'none';
    }
    this.visible = visible;
  };
  DRTS.Map.googlemaps.map.marker.prototype.getDraggable = function() {
    return this.draggable;
  };
  DRTS.Map.googlemaps.map.marker.prototype.setDraggable = function(draggable) {
    this.draggable = draggable;
  };
  DRTS.Map.googlemaps.map.marker.prototype.setZIndex = function(zIndex) {
    this.zIndex = zIndex;
    if (this.div) {
      this.div.style.zIndex = this.zIndex;
    }
  };
  DRTS.Map.googlemaps.map.marker.prototype.setAnimation = function(animation) {
    var class_name = 'drts-map-marker-bounce';
    if (animation) {
      if (this.classes.indexOf(class_name) === -1) {
        this.classes.push(class_name);
      }
    } else {
      var index = this.classes.indexOf(class_name);
      if (index > -1) {
        this.classes.splice(index, 1);
      }
    }
    if (this.div) {
      this.div.className = this.classes.join(' ');
    }
  };
})(jQuery);