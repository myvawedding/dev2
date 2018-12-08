'use strict';

(function($) {
  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (!data.clone.hasClass('drts-form-type-selecthierarchical')) return;

    var states = {},
      conditions,
      selects = data.clone.find('.drts-form-type-select'),
      selector = '.drts-form-field-select-';
    for (var i = 0; i < selects.length; ++i) {
      conditions = {};
      conditions[selector + i + ' select'] = {
        type: 'selected',
        value: true,
        container: '#' + data.clone.attr('id')
      };
      states[selector + (i + 1)] = {
        'load_options': {
          'conditions': conditions
        }
      };
    }
    DRTS.states(states, data.clone.closest('form'));
  });
})(jQuery);