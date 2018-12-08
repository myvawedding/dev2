'use strict';

(function($) {
  DRTS.Form.field.colorpicker = function(selector) {
    var $colorpicker = $(selector);
    if (!$colorpicker.length) return;

    var input = $colorpicker.find('input'),
      clear = $colorpicker.find('.drts-clear');

    var hueb = new Huebee(input.get(0), {
      saturations: input.data('saturations') || 1,
      notation: 'hex',
      hues: input.data('hues') || 12,
      customColors: input.data('custom-colors') || ['#CC2255', '#EE6622', '#EEAA00', '#1199FF', '#333333'],
      staticOpen: input.data('static-open') ? true : false
    });

    if (clear.length) {
      // Set clear icon position
      var pos;
      pos = input.outerWidth() - clear.outerWidth() - 5 + 'px';
      clear.css({
        top: input.outerHeight() / 2 + 'px',
        right: DRTS.isRTL ? pos : 'auto',
        left: DRTS.isRTL ? 'auto' : pos
      }).on('click', function() {
        input.val('').css('background-color', '');
        clear.css('visibility', 'hidden');
      });

      // Show clear icon if input has value, hide if not
      if (input.val().length > 0) clear.css('visibility', 'visible');
      input.on('keyup', function(e) {
        if (e.keyCode !== 13 && e.keyCode !== 27 && e.keyCode !== 32) {
          clear.css('visibility', input.val().length > 0 ? 'visible' : 'hidden');
        }
      });
      hueb.on('change', function(color, hue, sat, lum) {
        clear.css('visibility', 'visible');
      });
    }
  };
  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (data.clone.hasClass('drts-form-type-colorpicker')) {
      data.clone.find('input').css('background-color', '');
      DRTS.Form.field.colorpicker(data.clone);
    }
  });
})(jQuery);