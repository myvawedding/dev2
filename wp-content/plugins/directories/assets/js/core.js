"use strict";

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function(obj) {
  return typeof obj;
} : function(obj) {
  return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
};

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }
  return obj;
}

var DRTS = {
  isRTL: false,
  domain: null,
  path: '/',
  bsPrefix: "drts-bs-",
  params: {
    token: "_t_",
    contentType: "_type_",
    ajax: "_ajax_"
  }
};
(function($) {
  if (typeof console === 'undefined') {
    console = {
      log: function log() {}
    };
  }
  DRTS.init = function() {
    var _initTooltip = function _initTooltip(context) {
      var hasTouch = 'ontouchstart' in document.documentElement;
      if (!hasTouch) {
        $('[rel*="sabaitooltip"]', context).each(function() {
          var $this = $(this);
          $this.sabaiTooltip({
            container: $this.data('container') || context
          });
        });
      }
    };

    return function(context) {
      autosize($('textarea:visible', context));

      _initTooltip(context);

      if (window.cqApi) {
        // Re-evaluate CQ on collapsible/tab element shown
        $('.' + DRTS.bsPrefix + 'collapse', context).on('shown.bs.collapse', function(e) {
          window.cqApi.reevaluate(false);
        });
        $('a[data-toggle="' + DRTS.bsPrefix + 'tab"]', context).on('shown.bs.tab', function(e) {
          window.cqApi.reevaluate(false);
        });
      }

      var isIE11 = !!window.MSInputMethodContext && !!document.documentMode;
      if (isIE11) {
        $('.drts-grow,.drts-sm-grow,.drts-md-grow,.drts-lg-grow,.drts-xl-grow', context).removeClass('drts-grow drts-sm-grow drts-md-grow drts-lg-grow drts-xl-grow');
      }

      if (!DRTS.isMobile()) {
        $('a[data-phone-number]', context).contents().unwrap();
      }

      $(DRTS).trigger('drts_init.sabai', {
        context: context
      });
    };
  }();

  DRTS.cache = function() {
    var _cache = {};
    return function(id, data, lifetime) {
      if (arguments.length === 1) {
        if (!_cache[id]) {
          return false;
        }
        if (_cache[id]['expires'] < new Date().getTime()) {
          return false;
        }
        return _cache[id]['data'];
      }
      if (data === false) {
        delete _cache[id];
      } else {
        lifetime = lifetime || 600;
        _cache[id] = {
          data: data,
          expires: new Date().getTime() + lifetime * 1000
        };
      }
    };
  }();

  DRTS.flash = function(message, type, delay) {
    if (typeof message === 'undefined' || message === null || message === '') {
      return;
    }
    if (typeof message === 'string') {
      swal({
        title: message,
        type: type === 'danger' ? 'error' : type,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        showCloseButton: true,
        timer: type !== 'success' ? 60000 : undefined !== delay ? delay : 5000
      });
    } else {
      for (var i = 0; i < message.length; i++) {
        if (typeof message[i] === 'string') {
          DRTS.flash(message[i], type, delay);
        } else {
          DRTS.flash(message[i].msg, message[i].level, delay);
        }
      }
    }
  };

  DRTS.popover = function(target, options, force) {
    target = target instanceof jQuery ? target : $(target);
    if (!force && target.hasClass('drts-popover-processed')) return;
    options = options || {};
    if (!options.template) {
      options.template = '<div class="' + DRTS.bsPrefix + 'popover"><div class="' + DRTS.bsPrefix + 'arrow"></div>' + '<div class="' + DRTS.bsPrefix + 'popover-header"></div><div class="' + DRTS.bsPrefix + 'popover-body"></div>' + '</div>';
    }
    if (target.data('popover-url')) {
      options.url = target.data('popover-url');
    }
    if (options.url) {
      if (options.url.indexOf('#') === 0 || options.url.indexOf('.') === 0) {
        options.content = $(options.url).html();
      } else {
        var cache = DRTS.cache(options.url);
        if (!cache) {
          options.content = '<div class="drts-ajax-loader" style="position:relative;"></div>';
          $.get(options.url, _defineProperty({}, DRTS.params.ajax, 1), function(data) {
            DRTS.cache(options.url, data);
            target.attr('data-content', data).data('bs.popover').tip().find('.' + DRTS.bsPrefix + 'popover-body').html(data);
            target.sabaiPopover('show');
          });
        } else {
          options.content = cache;
        }
      }
    }
    if (!options.container) {
      options.container = target.closest('.drts');
    }
    if (target.data('popover-title')) {
      options.title = target.data('popover-title');
    }
    target.sabaiPopover(options).sabaiPopover('show').addClass('drts-popover-processed');
    var tip = $(target.data('bs.popover').getTipElement());
    tip.find('.' + DRTS.bsPrefix + 'close').on('click', function() {
      target.sabaiPopover('hide');
    });
    if (!DRTS.popoverInit) {
      $('body').on('click', function(e) {
        $('.drts-popover-processed').each(function() {
          var $this = $(this);
          //the 'is' for buttons that trigger popups
          //the 'has' for icons within a button that triggers a popup
          if (!$this.is(e.target) && $this.has(e.target).length === 0 && $('.' + DRTS.bsPrefix + 'popover').has(e.target).length === 0 && $(e.target).parents('.drts-popover-ignore-click').length === 0) {
            $this.sabaiPopover('hide');
          }
        });
      });
      DRTS.popoverInit = true;
    }
  };

  DRTS.modal = function() {
    var modal = $('#drts-modal');
    if (!modal.length) {
      modal = $('<div class="drts ' + DRTS.bsPrefix + 'modal' + (DRTS.isRTL ? ' drts-rtl' : '') + '" id="drts-modal" style="display:none;">' + '<div class="' + DRTS.bsPrefix + 'modal-dialog ' + DRTS.bsPrefix + 'modal-lg">' + '<div class="' + DRTS.bsPrefix + 'modal-content">' + '<div class="' + DRTS.bsPrefix + 'modal-header">' + '<div class="' + DRTS.bsPrefix + 'modal-title"></div>' + '<button class="' + DRTS.bsPrefix + 'close drts-modal-close" data-dismiss="modal"><span>&times;</span></button></div>' + '<div class="' + DRTS.bsPrefix + 'modal-body"></div>' + '<div class="' + DRTS.bsPrefix + 'modal-footer"></div>' + '</div></div></div>').prependTo('body');
      modal.on('shown.bs.modal', function(e) {
        var content = $(this).find('.' + DRTS.bsPrefix + 'modal-content');
        content.css('width', content.parent().outerWidth() + 'px'); // this seems to be needed for CQ re-evaluation on window resize
        if (window.cqApi) {
          window.cqApi.reevaluate(false);
        }
      });
    }
    return modal;
  };

  DRTS.ajaxLoader = function(trigger, remove, target) {
    var $trigger = trigger ? $(trigger) : false;
    if (target) {
      var $target = $(target);
      if (!$target.length) return;

      if (!remove) {
        var pos = $target.position();
        var ajaxloader = $('<div class="drts-ajax-loader"></div>').width($target.outerWidth()).height($target.outerHeight()).css({
          top: parseInt(pos.top, 10) + parseInt($target.css('margin-top'), 10) + 'px',
          left: parseInt(pos.left, 10) + parseInt($target.css('margin-left'), 10) + 'px',
          position: $target.css('position') === 'fixed' ? 'fixed' : 'absolute'
        });
        $target.after(ajaxloader);
      } else {
        $target.next('.drts-ajax-loader').remove();
      }
      if ($trigger && $trigger.length) {
        $trigger.blur().prop('disabled', !remove).css('pointer-events', remove ? 'auto' : 'none');
      }
    } else {
      if ($trigger && $trigger.length) {
        $trigger.blur().prop('disabled', !remove).css('pointer-events', remove ? 'auto' : 'none').toggleClass('drts-ajax-loading', !remove);
      }
    }
  };

  DRTS.ajax = function(options) {
    var o = $.extend({
        trigger: null,
        async: true,
        type: 'get',
        url: '',
        data: '',
        processData: true,
        target: '',
        container: null,
        modalTitle: null,
        modalHideOnSend: false,
        modalHideOnSuccess: true,
        cache: false,
        cacheId: null,
        cacheLifetime: 600,
        onSendData: null,
        onSuccess: null,
        onError: null,
        onErrorRedirect: false,
        onContent: null,
        onSuccessRedirect: false,
        onSuccessRedirectUrl: null,
        effect: null,
        scroll: false,
        replace: false,
        highlight: false,
        callback: false,
        loadingImage: true,
        position: false,
        toggle: false,
        pushState: false,
        pushStateUrl: null,
        state: {}
      }, options),
      target,
      targetSelector = '',
      overlay,
      _handleSuccess = function _handleSuccess(result, target) {
        if (o.trigger) {
          o.trigger.removeClass(DRTS.bsPrefix + 'disabled');
        }
        if (target && target.attr('id') === 'drts-modal' && o.modalHideOnSuccess) {
          target.sabaiModal('hide');
        }
        if (o.onSuccess) {
          o.onSuccess(result, target, o.trigger);
        }
        if (o.onSuccessRedirect) {
          if (result.url) {
            window.location = result.url;
            return;
          } else if (o.onSuccessRedirectUrl) {
            window.location = o.onSuccessRedirectUrl;
            return;
          }
        }
        if (result.messages) {
          DRTS.flash(result.messages, 'success');
        }
      },
      _handleError = function _handleError(error, target, status) {
        if (o.trigger) {
          o.trigger.removeClass(DRTS.bsPrefix + 'disabled');
        }
        if (o.onError) {
          o.onError(error, target, o.trigger, status);
        }
        if (o.onErrorRedirect && error.url) {
          window.location = error.url;
          return;
        }
        if (error.messages) {
          if (o.container === '#drts-modal') {
            target.sabaiModal('hide');
            DRTS.flash(error.messages, 'danger');
          } else if (o.trigger) {
            DRTS.popover(o.trigger, {
              content: error.messages[0].msg,
              html: true,
              container: o.trigger.closest('.drts'),
              title: o.trigger.attr('data-sabaipopover-title') || ''
            });
            o.trigger.attr('onclick', 'return false;');
          } else {
            DRTS.flash(error.messages, 'danger');
          }
        }
      },
      _handleContent = function _handleContent(response, target, isCache) {
        var html = typeof response === 'string' ? response : response.html;
        if (o.trigger) o.trigger.removeClass(DRTS.bsPrefix + 'disabled');
        if (o.container === '#drts-modal') {
          var title;
          if (o.trigger) {
            title = o.trigger.data('modal-title');
            if (typeof title === 'undefined') {
              title = o.trigger.attr('title') || o.trigger.attr('data-original-title') || o.trigger.text();
            }
          } else if (o.modalTitle) {
            title = o.modalTitle;
          }
          if (title) target.find('.' + DRTS.bsPrefix + 'modal-title').text(title);
          if (isCache) {
            target.addClass(DRTS.bsPrefix + 'fade');
          } else {
            // fade effect does not seem to work with non-cached content
            target.removeClass(DRTS.bsPrefix + 'fade');
          }
          var buttons = target.find('.' + DRTS.bsPrefix + 'modal-footer').empty().end().find('.' + DRTS.bsPrefix + 'modal-body').html(html).find('.drts-form-buttons');
          if (buttons.length) {
            target.find('.' + DRTS.bsPrefix + 'modal-footer').append(buttons);
          }
          target.sabaiModal('show').find('.' + DRTS.bsPrefix + 'modal-body').css({
            'max-height': 'calc(100vh - 200px)',
            'overflow-y': 'auto'
          });
          if (o.onContent) {
            o.onContent(response, target, o.trigger, isCache);
          }
        } else {
          if (o.replace) {
            // For now, no effect when replacing
            target = target.hide().after(html).remove().next();
            if (o.onContent) {
              o.onContent(response, target, o.trigger, isCache);
            }
          } else {
            if (!o.callback && target.attr('id') !== 'drts-content') {
              target.addClass('drts-ajax');
            }

            if (typeof html === 'string') {
              target.empty().html(html);
            } else {
              $.each(html, function(k, v) {
                target.find(k).empty().html(v);
              });
            }
            target.show();
            if (o.onContent) {
              o.onContent(response, target, o.trigger, isCache);
            }
          }
          if (o.highlight) {
            target.effect('highlight', {}, 1500);
          }
        }

        if (o.pushState && window.history && window.history.pushState) {
          var push_url = DRTS.filterUrl(o.pushStateUrl || o.url, [DRTS.params.contentType]);
          var params = [];
          $.each(push_url.query, function(i, val) {
            params.push(val[0] + '=' + encodeURIComponent(val[1]));
          });
          if (params.length) {
            push_url.search = '?' + params.join('&');
          }
          push_url = push_url.toString();
          o.state.data = o.data;
          o.state.url = push_url;
          o.state.container = o.container;
          o.state.target = o.target;
          window.history.pushState(o.state, null, push_url);
        }

        DRTS.init(target);

        $(DRTS).trigger('loaded.sabai', {
          container: o.container,
          target: target,
          response: response,
          context: target
        });
      };
    if (o.trigger) {
      if (o.trigger.hasClass(DRTS.bsPrefix + 'disabled')) {
        return;
      }
      if (!o.url) o.url = o.trigger.data('url');
      if (o.trigger.hasClass(DRTS.bsPrefix + 'dropdown-link')) {
        o.trigger = o.trigger.closest('.' + DRTS.bsPrefix + 'btn-group').find('.' + DRTS.bsPrefix + 'dropdown-toggle');
      }
      o.trigger.addClass(DRTS.bsPrefix + 'disabled');
    }
    if (!o.url) return;
    if (o.container) {
      targetSelector = o.container;
      if (o.container === '#drts-modal') {
        target = DRTS.modal();
      } else {
        if (o.target) {
          target = $(o.container).find(o.target);
          targetSelector = o.container + ' ' + o.target;
        } else {
          target = $(o.container);
        }
        if (!target.length) {
          console.log(targetSelector);
          return;
        }
      }
    }
    if (o.url.indexOf('#') === 0 || o.url.indexOf('.') === 0) {
      _handleContent($(o.url).html(), target, true);
      return;
    }
    if (o.cache && o.type === 'get') {
      var cached = DRTS.cache(o.cacheId ? o.cacheId : o.container + ' ' + o.url.replace(new RegExp('&' + DRTS.params.contentType + '=(html|json)', 'g'), ''));
      if (cached) {
        // Scroll to the updated content? We need to scroll before replace otherwise scroll target will not exist.
        if (o.scroll && targetSelector) {
          DRTS.scrollTo(!$.isNumeric(o.scroll) && typeof o.scroll !== 'boolean' ? targetSelector + ' ' + o.scroll : targetSelector);
        }
        _handleContent(cached, target, true);
        return;
      }
    }
    if (o.onSendData) {
      if (_typeof(o.data) !== 'object') {
        o.data = {};
      }
      o.onSendData(o.data, o.trigger);
    }
    if (_typeof(o.data) === 'object' && o.data !== null) {
      if (!o.data.hasOwnProperty(DRTS.params.ajax)) {
        o.data[DRTS.params.ajax] = targetSelector || 1;
      }
      o.data = $.param(o.data);
    } else if (typeof o.data === 'string' && o.data !== '') {
      o.data += '&' + DRTS.params.ajax + '=' + (targetSelector ? encodeURIComponent(targetSelector) : 1);
    } else {
      o.data = DRTS.params.ajax + '=' + (targetSelector ? encodeURIComponent(targetSelector) : 1);
    }
    $.ajax({
      global: true,
      async: o.async,
      type: o.type,
      dataType: 'html',
      url: o.url,
      data: o.data,
      processData: o.processData,
      cache: false,
      beforeSend: function beforeSend(xhr) {
        // displya ajax loading image
        if (target && target.attr('id') === 'drts-modal') {
          if (o.modalHideOnSend) {
            target.sabaiModal('hide');
          } else {
            var title;
            if (o.trigger) {
              title = o.trigger.data('modal-title');
              if (typeof title === 'undefined') {
                title = o.trigger.attr('title') || o.trigger.attr('data-original-title') || o.trigger.text();
              }
            } else if (o.modalTitle) {
              title = o.modalTitle;
            }
            if (title) target.find('.' + DRTS.bsPrefix + 'modal-title').text(title);
            if (o.loadingImage) {
              target.removeClass(DRTS.bsPrefix + 'fade').find('.' + DRTS.bsPrefix + 'modal-body').empty().addClass('drts-ajax-loading').end().find('.' + DRTS.bsPrefix + 'modal-footer').empty().end().sabaiModal('show');
            }
          }
        } else {
          // Scroll to the updated content?
          if (o.scroll && targetSelector) {
            DRTS.scrollTo(!$.isNumeric(o.scroll) && typeof o.scroll !== 'boolean' ? targetSelector + ' ' + o.scroll : targetSelector);
          }

          if (!o.loadingImage) return;
          if (target && target.attr('id') !== 'drts-content' && target.is(':visible')) {
            overlay = target;
          }
          if (o.trigger || overlay) DRTS.ajaxLoader(o.trigger, false, overlay);
        }
      },
      complete: function complete(xhr, textStatus) {
        if (o.loadingImage) {
          if (target && target.attr('id') === 'drts-modal') {
            target.find('.' + DRTS.bsPrefix + 'modal-body').removeClass('drts-ajax-loading');
          } else {
            var _overlay = o.replace ? targetSelector : overlay;
            if (o.trigger || _overlay) DRTS.ajaxLoader(o.trigger, true, _overlay);
          }
        }
        switch (textStatus) {
          case 'success':
            if (xhr.status === 278 || xhr.getResponseHeader('content-type').indexOf('json') > -1) {
              // Response was success
              try {
                var result = JSON.parse(xhr.responseText.replace(/<!--[\s\S]*?-->/g, ''));
                if (typeof result.html !== 'undefined') {
                  _handleContent(result, target);
                  if (o.cache && o.type === 'get') {
                    DRTS.cache(o.cacheId ? o.cacheId : o.container + ' ' + o.url.replace(new RegExp('&' + DRTS.params.contentType + '=(html|json)', 'g'), ''), result, o.cacheLifetime);
                  }
                } else {
                  _handleSuccess(result, target);
                }
              } catch (e) {
                console.log(e.toString(), xhr.responseText);
              }
            } else {
              // Response was HTML/JSON
              _handleContent(xhr.responseText, target);
              if (o.cache && o.type === 'get') {
                DRTS.cache(o.cacheId ? o.cacheId : o.container + ' ' + o.url.replace(new RegExp('&' + DRTS.params.contentType + '=(html|json)', 'g'), ''), xhr.responseText, o.cacheLifetime);
              }
            }
            break;
          case 'error':
            try {
              var result = JSON.parse(xhr.responseText.replace(/<!--[\s\S]*?-->/g, ''));
              _handleError(result, target, xhr.status);
            } catch (e) {
              console.log(e.toString(), xhr.responseText);
            }
            break;
        }
      }
    });
  };

  DRTS.scrollTo = function(target, duration, offset, callback) {
    target = target instanceof jQuery ? target : $(target);
    if (!target.length) return;
    duration = typeof duration !== 'undefined' && duration !== null ? duration : 200;
    offset = typeof offset !== 'undefined' && offset !== null ? offset : 0;
    if ($('#wpadminbar').length) offset -= $('#wpadminbar').outerHeight();
    $('html, body').animate({
      scrollTop: target.offset().top + offset,
      duration: duration
    }).promise().done(callback);
  };

  DRTS.states = function(states, context) {
    var initial_triggers = [],
      inverted_actions = {
        visible: 'invisible',
        visible_enable: 'invisible_disable',
        unchecked: 'checked',
        'unload_options': 'load_options',
        'show_options': 'hide_options',
        'enable_options': 'disable_options',
        enabled: 'disabled',
        unplaceholder: 'placeholder'
      },
      _addRule = function _addRule(selector, action, conditions, context) {
        var $dependent = $(selector, context);
        if (!$dependent.length) {
          return;
        }

        $dependent.each(function() {
          var $dependee,
            $form,
            condition,
            events,
            form,
            $_dependent = $(this),
            _event;
          if (!$_dependent.data('guid')) {
            $_dependent.data('guid', DRTS.guid());
          }
          for (var i in conditions) {
            condition = conditions[i];
            form = condition['container'] || 'form';
            $form = $_dependent.closest(form);
            if (!$form.length) {
              console.log('Invalid or non existent container selector: ' + (condition['container'] || 'form'));
              conditions.splice(i, 1); // remove from conditions array
              continue;
            }
            $dependee = $form.find(condition['target']);
            if (!$dependee.length) {
              console.log('Invalid or non existent dependee selector: ' + condition['target']);
              conditions.splice(i, 1); // remove from conditions array
              continue;
            }

            events = ['initialized.sabai'];
            switch (condition['type']) {
              case 'selected':
              case 'unselected':
                events.push('change', 'cloneremoved.sabai');
                break;
              case 'checked':
              case 'unchecked':
                events.push('change', 'cloneremoved.sabai', 'switchChange.bootstrapSwitch');
                break;
              case 'focus':
              case 'blur':
                events.push('focus', 'blur');
                break;
              case 'values':
              case 'count':
              default:
                // default type is "value"
                events.push('keyup', 'change', 'cloneremoved.sabai');
            }
            initial_triggers.push($dependee);
            for (var i = 0; i < events.length; i++) {
              _event = events[i] + '.sabai.' + action + '.' + $_dependent.data('guid'); // add action and guid so it can be targetted later for removal
              $dependee.off(_event).on(_event, function(dependee, dependent, action, conditions, context, form) {
                return function(e, isInit) {
                  _applyRule(dependee, dependent, action, conditions, context, form, e.type, isInit);
                };
              }($dependee, $_dependent, action, conditions, context, form));
            }
          }
        });
      },
      _applyRule = function _applyRule($dependee, $dependent, action, conditions, context, container, event, isInit) {
        var flag, $_dependee, condition, $dependent;
        if (action.match(/_or$/)) {
          flag = false;
          action = action.slice(0, action.length - 3);
          for (var i in conditions) {
            condition = conditions[i];
            $_dependee = $dependent.closest(container).find(condition['target']);
            if (!$_dependee.length) {
              return;
            }

            if (_isConditionMet($_dependee, condition['type'] || 'value', condition['value'])) {
              flag = true;
              break;
            }
          }
        } else {
          flag = true;
          for (var i in conditions) {
            condition = conditions[i];
            $_dependee = $dependent.closest(container).find(condition['target']);
            if (!$_dependee.length) {
              return;
            }
            if (!_isConditionMet($_dependee, condition['type'] || 'value', condition['value'])) {
              flag = false;
              break;
            }
          }
        }
        if (action in inverted_actions) {
          action = inverted_actions[action];
          flag = !flag;
        }
        _doAction($dependent, action, flag, $dependee, event, isInit);
      },
      _isConditionMet = function _isConditionMet($dependee, type, value) {
        switch (type) {
          case 'value':
          case '!value':
          case 'one':
            if ((typeof value === "undefined" ? "undefined" : _typeof(value)) !== 'object') {
              // convert to an array
              value = [value];
            }
            var dependee_val = [];
            $dependee.each(function() {
              var ele = this;
              if (ele.nodeName === 'INPUT') {
                if (ele.type === 'checkbox' || ele.type === 'radio') {
                  if (!ele.checked) {
                    return true;
                  }
                }
              } else if (ele.nodeName === 'SELECT') {
                if (!ele.options[ele.selectedIndex]) {
                  return true;
                }
                ele = ele.options[ele.selectedIndex];
              } else {
                return true;
              }
              dependee_val.push(ele.value);
              if (ele.hasAttribute('data-alt-value')) {
                dependee_val.push(ele.getAttribute('data-alt-value'));
              }
            });
            var value_length = value.length,
              dependee_val_length = dependee_val.length;
            loop1: for (var i = 0; i < value_length; i++) {
              loop2: for (var j = 0; j < dependee_val_length; j++) {
                if (value[i] == dependee_val[j]) {
                  if (type === '!value') return false;
                  if (type === 'one') return true;
                  continue loop1;
                }
              }
              // One of values did not match
              if (type === 'value') return false;
            }
            // All matched or did not match.
            return type !== 'one' ? true : false;
          case '^value':
            var dependee_val = $dependee.val();
            return typeof dependee_val === 'string' && dependee_val.startsWith(value);
          case '$value':
            var dependee_val = $dependee.val();
            return typeof dependee_val === 'string' && dependee_val.endsWith(value);
          case '*value':
            var dependee_val = $dependee.val();
            return typeof dependee_val === 'string' && dependee_val.includes(value);
          case '>value':
            return $.isNumeric(value) && $.isNumeric($dependee.val()) && Number($dependee.val()) > Number(value);
          case '<value':
            return $.isNumeric(value) && $.isNumeric($dependee.val()) && Number($dependee.val()) < Number(value);
          case 'count':
            var dependee_val = [];
            $dependee.each(function() {
              if (this.type === 'checkbox' || this.type === 'radio') {
                if (this.checked) {
                  dependee_val.push(this.value);
                }
              } else {
                dependee_val.push($(this).val());
              }
            });
            return dependee_val.length === value;
          case 'checked':
          case 'unchecked':
            var result = false;
            $dependee.each(function() {
              if ($(this).prop('checked') === Boolean(value)) {
                result = true;
                return false; // breaks each()
              }
            });
            return type === 'checked' ? result : !result;
          case 'empty':
          case 'filled':
          case 'selected':
            var result = false;
            $dependee.each(function() {
              if (this.type === 'checkbox' || this.type === 'radio') {
                if ($(this).prop('checked') !== Boolean(value)) {
                  result = true;
                  return false; // breaks each()
                }
              } else {
                if ($.trim($(this).val()) === '' === Boolean(value)) {
                  result = true;
                  return false; // breaks each()
                }
              }
            });
            return type === 'empty' ? result : !result;
          case 'focus':
            return $dependee.is(':focus') === Boolean(value);
          case 'blur':
            return $dependee.is(':focus') !== Boolean(value);
          default:
            alert('Invalid condition type: ' + type);
            return false;
        }
      },
      _doAction = function _doAction($dependent, action, flag, $dependee, event, isInit) {
        switch (action) {
          case 'invisible':
          case 'invisible_disable':
            $dependent.toggleClass('drts-form-states-invisible', flag);
            if (flag) {
              if (isInit) {
                if ($dependent.hasClass('drts-form-has-error')) {
                  $dependent.show();
                  flag = false;
                } else {
                  $dependent.hide();
                }
              } else {
                $dependent.hide();
              }
            } else if ($dependent.is(':hidden')) {
              if (isInit) {
                $dependent.show();
              } else {
                $dependent.css('opacity', 0).slideDown(100).animate({
                  opacity: 1
                }, {
                  queue: false,
                  duration: 'slow'
                });
                if ($dependent.hasClass('drts-form-field') && $dependent.parent('.' + DRTS.bsPrefix + 'form-inline').length) {
                  $dependent.css('display', 'inline-block');
                }
              }
            }
            if (action === 'invisible_disable') {
              $dependent.find(':input').prop('disabled', flag);
            }
            break;
          case 'disabled':
            if (flag) {
              $dependent.css({
                position: 'relative',
                opacity: 0.5
              });
              if (!$dependent.find('> .drts-cover').length) {
                $dependent.append('<div class="drts-cover" style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;"></div>');
              }
            } else {
              $dependent.css({
                opacity: 1
              }).find('> .drts-cover').remove();
            }
            $dependent.find(':input').prop('disabled', flag);
            break;
          case 'checked':
            $dependent.find(':checkbox').prop('checked', flag).change();
            break;
          case 'placeholder':
            var texts = $dependent.find('input[type="text"]');
            if (!texts.length) return;

            if (flag) {
              var value = $dependee.val();
              texts.each(function() {
                var $this = $(this);
                if (typeof $this.attr('original-placeholder') === 'undefined') {
                  $this.attr('original-placeholder', $this.attr('placeholder') || '');
                }
                $this.attr('placeholder', $this.data(DRTS.camelize('placeholder-' + value)) || '');
              });
            } else {
              texts.each(function() {
                var $this = $(this);
                $this.attr('placeholder', $this.data('original-placeholder') || '');
              });
            }
            break;
          case 'load_options':
            if (event !== 'change' && event !== 'initialized') return;

            var dropdown = $dependent.find('select');
            dropdown.find('option[value!=""]').remove();
            if (flag) {
              var url = dropdown.data('load-url'),
                cacheId = url + $dependee.val(),
                data = DRTS.cache(cacheId),
                prefix = dropdown.data('options-prefix') || '',
                success = function success(data) {
                  var display;
                  DRTS.cache(cacheId, data);
                  if (typeof data !== 'undefined' && data.length === 0) {
                    if (!dropdown.data('show-empty')) {
                      $dependent.hide();
                    }
                    // clear default value and trigger change event
                    dropdown.data('default-value', '').val('');
                    dropdown.trigger('change', [isInit]);
                    return;
                  }
                  $.each(data, function(index, val) {
                    dropdown.append($('<option></option>').attr('data-alt-value', val.name).text(prefix + val.title).val(val.id));
                  });
                  if (!$dependent.closest('.drts-cloned').length) {
                    var default_value = dropdown.data('default-value');
                    if (typeof default_value !== 'undefined') {
                      dropdown.val(default_value);
                      dropdown.trigger('change', [isInit]);
                    }
                  }
                  if (!$dependent.hasClass('drts-form-states-invisible') && $dependent.is(':hidden') && dropdown.find('option[value!=""]').length) {
                    $dependent.hide().addClass('drts-was-hidden');
                    if ($dependent.hasClass(DRTS.bsPrefix + 'row') || $dependent.hasClass(DRTS.bsPrefix + 'form-row')) {
                      display = 'flex';
                    } else {
                      display = $dependent.hasClass('drts-form-field') && $dependent.parent('.' + DRTS.bsPrefix + 'form-inline').length ? 'inline' : 'block';
                    }
                    if (isInit) {
                      $dependent.css('display', display);
                    } else {
                      $dependent.hide().css('display', display).fadeIn('fast');
                    }
                  }
                };
              if (data !== false) {
                success(data);
              } else {
                $dependee.addClass('drts-ajax-loading');
                $.getJSON(url, {
                  parent: $dependee.val()
                }, success).always(function() {
                  $dependee.removeClass('drts-ajax-loading');
                });
              }
            } else {
              if (!$dependent.is(':hidden') && $dependent.hasClass('drts-was-hidden')) {
                $dependent.hide();
                // clear default value and trigger change event
                dropdown.data('default-value', '').val('');
                dropdown.trigger('change', [isInit]);
              }
            }
            break;
          case 'disable_options':
            var value = $dependee.val(),
              options_allowed = $dependent.data('options') && $dependent.data('options')[value] ? $dependent.data('options-allowed')[value] : [];
            $dependent.find('option').each(function() {
              var $this = $(this);
              if (-1 !== $.inArray($this.attr('value'), options_allowed)) {
                // found
                $this.prop('disabled', flag);
              } else {
                $this.prop('disabled', !flag);
              }
            });
            break;
          case 'hide_options':
            var dependee_val = [];
            $dependee.each(function() {
              if (this.type === 'checkbox' || this.type === 'radio') {
                if (this.checked) {
                  dependee_val.push(this.value);
                }
              } else {
                dependee_val.push($(this).val());
              }
            });
            if (!flag || !dependee_val.length) return;
            $dependent.find('input').each(function() {
              var $this = $(this),
                values = $this.data('values'),
                field = $this.closest('.drts-form-field-radio-option');
              if (!field.length) return;

              for (var i = 0; i < dependee_val.length; i++) {
                if (-1 !== $.inArray(dependee_val[i], values)) {
                  field.slideDown(100);
                  return;
                }
              }
              if (this.type === 'checkbox' || this.type === 'radio') {
                this.checked = false;
              }
              field.hide();
            });
            break;
          case 'slugify':
            if (!isInit) {
              $dependent.find('input').val($dependee.val()).change();
            }
            break;
          default:
            alert('Invalid action: ' + action);
        }
      };

    for (var selector in states) {
      for (var action in states[selector]) {
        _addRule(selector, action, states[selector][action]['conditions'], context);
      }
    }
    for (var j in initial_triggers) {
      if (initial_triggers[j].data('initialized')) continue;

      initial_triggers[j].trigger('initialized.sabai', [true]).data('initialized', true);
    }
  };

  DRTS.camelize = function(str) {
    return str.replace(/^([A-Z])|[\s-_]+(\w)/g, function(match, p1, p2) {
      if (p2) return p2.toUpperCase();
      return p1.toLowerCase();
    });
  };

  DRTS.cloneField = function(container, fieldsSelector, maxNum, nextIndex) {
    var $container = $(container),
      fields,
      index;
    fields = $container.find(fieldsSelector);
    index = nextIndex || fields.length;
    if (maxNum && fields.length >= maxNum) return;

    var field = fields.first(),
      id = 'drts-' + DRTS.guid(),
      clone = field.clone().addClass('drts-cloned').attr('id', id).find(':input,:hidden').each(function() {
        var $this = $(this);
        if ($this.attr('name')) {
          $this.attr('name', $this.attr('name').replace(/\[\d+\]/, '[' + index + ']'));
        }
        if ($this.attr('id')) {
          $this.attr('id', $this.attr('id') + '-' + index);
        }
        // Make sure default value is empty
        $this.removeData('default-value').removeAttr('data-default-value');
        // Fix for jquery.uniform
        if ($.fn.uniform && $this.parent().is('.selector')) {
          $this.prev('span').remove().end().unwrap().uniform().parent('.selector').show();
        }
      }).end().clearInput().find('label').each(function() {
        var $this = $(this);
        if ($this.attr('for')) {
          $this.attr('for', $this.attr('for') + '-' + index);
        }
      }).end().removeClass('drts-form-has-error').find('.drts-form-error').text('').end().find('.drts-form-has-error').each(function() {
        $(this).removeClass('drts-form-has-error');
      }).end().find('.drts-was-hidden').hide().end().hide().insertAfter(fields.last());
    clone.addClass('drts-form-field-removable').css('position', 'relative').append('<button class="' + DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-danger ' + DRTS.bsPrefix + 'btn-sm drts-form-field-remove"><i class="fas fa-times"></i></button>').slideDown(100);
    $(DRTS).trigger('clonefield.sabai', {
      container: container,
      field: field,
      clone: clone,
      index: index
    });
  };

  DRTS.guid = function() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random() * 16 | 0,
        v = c == 'x' ? r : r & 0x3 | 0x8;
      return v.toString(16);
    });
  };

  if (window.history && window.history.pushState) {
    var popped = false,
      initial_url = window.location.href.replace(/%2F/g, '/');
    $(window).on('popstate', function(e) {
      // Ignore inital popstate that some browsers fire on page load
      if (!popped) {
        popped = true;
        if (location.href.replace(/%2F/g, '/') == initial_url) {
          return;
        }
      }
      var state = e.originalEvent.state;
      if (state) {
        state.data = state.data.replace(/(^\?)/, '').split('&').map(function(n) {
          return n = n.split('='), this[n[0]] = n[1], this;
        }.bind({}))[0];
        state.data[DRTS.params.ajax] = state.target ? state.container + ' ' + state.target : state.container;
        DRTS.ajax(state);
        $(DRTS).trigger('sabaipopstate.sabai', state);
      } else {
        $(window).off('popstate');
        window.location.href = window.location.href;
      }
    });
  };

  DRTS.filterUrl = function(url, filter) {
    var loc = url ? $('<a/>').prop('href', url)[0] : window.location;
    loc.query = [];
    if (loc.search && typeof loc.search == 'string') {
      var params = loc.search.substr(1).replace(/\+/g, '%20').split('&');
      $.each(params, function(i, val) {
        var param = val.split('=');
        if (!param[1].length // remove empty
          ||
          filter && $.inArray(param[0], filter) !== -1 // remove unwanted
        ) return;

        try {
          param[1] = decodeURIComponent(param[1]);
        } catch (e) {}
        loc.query.push(param);
      });
    }
    return loc;
  };

  DRTS.isMobile = function() {
    return (/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase()));
  };

  $.fn.sabaiAlert = $.fn.alert.noConflict();
  $.fn.sabaiButton = $.fn.button.noConflict();
  $.fn.sabaiCarousel = $.fn.carousel.noConflict();
  $.fn.sabaiCollapse = $.fn.collapse.noConflict();
  $.fn.sabaiDropdown = $.fn.dropdown.noConflict();
  $.fn.sabaiModal = $.fn.modal.noConflict();
  $.fn.sabaiTooltip = $.fn.tooltip.noConflict();
  $.fn.sabaiPopover = $.fn.popover.noConflict();
  $.fn.sabaiScrollspy = $.fn.scrollspy.noConflict();
  $.fn.sabaiTab = $.fn.tab.noConflict();

  $.fn.clearInput = function() {
    return this.each(function() {
      var $this = $(this),
        tag = $this.get(0).tagName.toLowerCase();
      if (typeof $this.data('default-value') !== 'undefined') {
        return $this.val($this.data('default-value'));
      }
      if (tag === 'input') {
        var type = $this.attr('type');
        return type === 'checkbox' || type === 'radio' ? $this.prop('checked', false) : $this.val('');
      } else if (tag === 'textarea') {
        return $this.val('');
      } else if (tag === 'select') {
        if ($this.find('option[value=""]').length > 0) {
          return $this.val('');
        }
        return $this.prop('selectedIndex', 0);
      } else {
        return $this.find(':input').clearInput();
      }
    });
  };
})(jQuery);