'use strict';

(function($) {
  var DRTSYoastSEO = function DRTSYoastSEO() {
    YoastSEO.app.registerPlugin('directories', {
      status: 'ready'
    });
    YoastSEO.app.registerModification('content', this.myContentModification, 'directories', 5);
  };

  DRTSYoastSEO.prototype.myContentModification = function(data) {
    return $('[data-form-field-name="drts[post_content][0]"]').find('textarea').val();
  };

  $(window).on('YoastSEO:ready', function() {
    new DRTSYoastSEO();
  });
})(jQuery);