'use strict';

(function($) {
  DRTS.Form.field.timepicker = function(selector) {
    var $time = $(selector),
      config,
      $time_start,
      $time_end,
      $time_start_clear,
      $time_end_clear;
    if (!$time.length) return;

    config = {
      dateFormat: 'H:i',
      enableTime: true,
      noCalendar: true,
      mode: 'single',
      locale: $time.data('date-locale') || null,
      time_24hr: $time.data('date-ampm') ? false : true,
      static: $time.data('date-static') || $time.closest('.' + DRTS.bsPrefix + 'modal').length > 0
    };

    $time_start = $time.find('.drts-form-timepicker-start input');
    if (!$time_start.length) return;
    $time_start_clear = $time_start.closest('.drts-form-flatpickr').find('.drts-clear');
    $time_start.flatpickr($.extend({}, config, {
      defaultDate: $time_start.data('date-default-date') || null,
      onValueUpdate: function onValueUpdate() {
        $time_start_clear.length && $time_start_clear.css('visibility', $time_start.val().length > 0 ? 'visible' : 'hidden');
      },
      onReady: function onReady() {
        $time_start_clear.length && $time_start_clear.css('visibility', $time_start.val().length > 0 ? 'visible' : 'hidden');
      }
    }));

    $time_end = $time.find('.drts-form-timepicker-end input');
    if ($time_end.length) {
      $time_end_clear = $time_end.closest('.drts-form-flatpickr').find('.drts-clear');
      $time_end.flatpickr($.extend({}, config, {
        defaultDate: $time_end.data('date-default-date') || null,
        onValueUpdate: function onValueUpdate() {
          $time_end_clear.length && $time_end_clear.css('visibility', $time_end.val().length > 0 ? 'visible' : 'hidden');
        },
        onReady: function onReady() {
          $time_end_clear.length && $time_end_clear.css('visibility', $time_end.val().length > 0 ? 'visible' : 'hidden');
        }
      }));
    }

    $time.on('click', '.drts-clear', function() {
      $(this).css('visibility', 'hidden').closest('.drts-form-flatpickr').find('.flatpickr-input').val('').closest('form').trigger('change.sabai'); // for some reason triggering change event on input doesn't work, so trigger form directly
    });

    // For resetting field
    $time.on('entity_reset_form_field.sabai', function() {
      $(this).find('.drts-form-timepicker-day select').val('').end().find('.flatpickr-input').val('');
    });
  };

  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (data.clone.hasClass('drts-form-type-timepicker')) {
      data.clone.find('[data-date-default-date]').removeAttr('data-date-default-date');
      DRTS.Form.field.timepicker(data.clone);
    }
  });
})(jQuery);