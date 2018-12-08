'use strict';

(function($) {
  DRTS.Form.field.datepicker = function(selector) {
    var $date, $date_date, $date_clear, config, mode, format, altFormat;

    $date = $(selector);
    if (!$date.length) return;

    $date_date = $date.find('.drts-form-datepicker-date');
    if (!$date_date.length) return;

    // Manually un-apply flapickr if already there. This is primarily for modal filter form.
    $date.find('.flatpickr-input').each(function() {
      var $this = $(this);
      if ($this.attr('type') === 'text') {
        $this.remove();
      } else {
        $this.attr('type', 'text');
      }
    });

    $date_clear = $date_date.closest('.drts-form-flatpickr').find('.drts-clear');
    mode = $date_date.data('date-mode') || 'single';
    format = $date_date.data('date-enable-time') ? 'Y/m/d H:i' : 'Y/m/d';
    altFormat = $date_date.data('date-display-format') || format;
    config = {
      altFormat: altFormat,
      altInput: true,
      dateFormat: format,
      minDate: $date_date.data('date-min') || null,
      maxDate: $date_date.data('date-max') || null,
      enableTime: $date_date.data('date-enable-time') ? true : false,
      time_24hr: true,
      defaultDate: $date_date.data('date-default-date') || null,
      locale: $date_date.data('date-locale') || null,
      mode: mode,
      static: $date_date.data('date-static') ? true : false,
      onValueUpdate: function onValueUpdate() {
        $date_clear.length && $date_clear.css('visibility', $date_date.val().length > 0 ? 'visible' : 'hidden');
      },
      onReady: function onReady() {
        $date_clear.length && $date_clear.css('visibility', $date_date.val().length > 0 ? 'visible' : 'hidden');
      },
      onChange: function onChange(selectedDates, dateStr, instance) {
        var form,
          submit = false;
        switch (mode) {
          case 'range':
            if (selectedDates.length === 2) {
              $date_date.val(instance.formatDate(selectedDates[0], format) + ' to ' + instance.formatDate(selectedDates[1], format));
              submit = true;
            }
            break;
          case 'single':
            $date_date.data('alt-value', instance.parseDate(dateStr, format).getTime() / 1000);
            submit = true;
            break;
          default:
        }
        if (submit && !$date.closest('.' + DRTS.bsPrefix + 'modal').length // do not auto-submit when in modal
        ) {
          var form = $date_date.closest('form');
          if (form.length > 0 && form.hasClass('drts-view-filter-form')) {
            form.submit();
          }
        }
      }
    };

    if ($date_date.get(0)._flatpickr) $date_date.get(0)._flatpickr.destroy();
    $date_date.flatpickr(config);

    $date.off('click.sabai').on('click.sabai', '.drts-clear', function() {
      $(this).css('visibility', 'hidden').closest('.drts-form-flatpickr').find('.flatpickr-input').val('').closest('form').trigger('change.sabai'); // for some reason triggering change event on input doesn't work, so trigger form directly
    });

    // For resetting field
    $date.off('entity_reset_form_field.sabai').on('entity_reset_form_field.sabai', function() {
      $(this).find('.flatpickr-input').val('');
    });
  };

  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (data.clone.hasClass('drts-form-type-datepicker')) {
      DRTS.Form.field.datepicker(data.clone);
    }
  });
})(jQuery);