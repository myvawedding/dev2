'use strict';

(function($) {
  DRTS.Voting = DRTS.Voting || {};
  DRTS.Voting.onSendData = function(type, trigger, data) {
    // Temporarily store original label
    if (trigger.data('success-label')) {
      var label = trigger.find('.drts-voting-vote-label');
      if (label.length) {
        trigger.data('original-label', label.text());
        label.text(trigger.data('success-label'));
      }
    }
    // Toggle icon
    trigger.find('i').attr('class', trigger.data(trigger.hasClass(DRTS.bsPrefix + 'active') ? 'voting-icon' : 'voting-icon-active'));
  };
  DRTS.Voting.onSuccess = function(type, trigger, result) {
    // Set original button label as the new success label
    if (trigger.data('original-label')) {
      trigger.data('success-label', trigger.data('original-label')).removeData('original-label');
    }
    // Toggle active status
    trigger.closest('.drts-display-element[data-name="button"]').find('button[data-voting-type="' + type + '"]').each(function() {
      var $this = $(this),
        active = result.value == $this.data('active-value');
      $this.toggleClass(DRTS.bsPrefix + 'active', active).find('i').attr('class', $this.data(active ? 'voting-icon-active' : 'voting-icon'));
      if ($this.find('.drts-voting-vote-num').length) {
        $this.find('.drts-voting-vote-num').text($this.data('active-value') < 0 ? result.num_down : result.num);
      }
    });
  };
  DRTS.Voting.onError = function(type, trigger, error) {
    // Restore original button label
    if (trigger.data('original-label')) {
      var label = trigger.find('.drts-voting-vote-label');
      if (label.length) {
        label.text(trigger.data('original-label'));
        trigger.removeData('original-label');
      }
    }
    // Set icon
    trigger.find('i').attr('class', trigger.data(trigger.hasClass(DRTS.bsPrefix + 'active') ? 'voting-icon-active' : 'voting-icon'));
  };
})(jQuery);