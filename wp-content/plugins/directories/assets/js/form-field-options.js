'use strict';

(function($) {
  DRTS.Form.field.options = {};
  DRTS.Form.field.options.add = function(container, fieldName, trigger, isCheckbox, callback) {
    var $container = $(container),
      $original = $(trigger).closest('.drts-form-field-option'),
      optionsSelector = $container.children('[class*="' + DRTS.bsPrefix + 'col-"]').length ? '> div > div > .drts-form-field-option' // horizontal fields
      :
      '> div > .drts-form-field-option',
      options = $container.find(optionsSelector),
      choiceName = isCheckbox ? fieldName + "[default][]" : fieldName + "[default]",
      i = $original.find("input[name='" + choiceName + "']").val(),
      option = $original.clone().toggleClass('drts-form-field-option-new', true).find(':text,:hidden').each(function() {
        var $this = $(this);
        if (!$this.attr('name')) return;
        $this.attr('name', $this.attr('name').replace(fieldName + '[options][' + i + ']', fieldName + '[options][' + options.length + ']'));
      }).end().clearInput().find("input[name='" + choiceName + "']").val(options.length).end(),
      icon = option.find(".drts-form-field-option-icon");
    if (icon.length) {
      new DRTS.Form.field.iconpicker(icon);
    }
    option.hide().insertAfter($original);
    if (callback) {
      callback.call(null, option);
    }
    option.slideDown(100);
    return false;
  };

  DRTS.Form.field.options.remove = function(container, trigger, confirmMsg) {
    var $container = $(container),
      options_non_disabled = $container.find('.drts-form-field-option:not(.drts-form-field-option-disabled)');
    if (options_non_disabled.length === 1) {
      // There must be at least one non-disabled optoin, so just clear it instead of removing
      options_non_disabled.clearInput();
      return;
    }
    // Confirm deletion
    if (!confirm(confirmMsg)) return false;
    $(trigger).closest('.drts-form-field-option').slideUp('fast', function() {
      $(this).remove();
    });
  };
})(jQuery);