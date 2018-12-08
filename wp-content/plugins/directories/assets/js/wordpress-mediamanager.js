'use strict';

(function($) {
  DRTS.WordPress = DRTS.WordPress || {};
  DRTS.WordPress.mediamanager = DRTS.WordPress.mediamanager || function(o) {
    var options = $.extend({}, {
      selector: '',
      maxNumFiles: 0,
      maxNumFileExceededError: '',
      fileNotAllowedError: '',
      sortable: true
    }, o);

    var $button = $(options.selector);
    if (!$button.length) return;

    var frames = {},
      table = $button.closest('.drts-form-type-wp-media-manager').find('.drts-wp-upload-current table');
    $button.on('click', function(e) {
      e.preventDefault();
      var $this = $(this),
        name = $this.data('input-name'),
        mtypes;

      if (!frames[name]) {
        mtypes = $this.data('mime-types').split(',');

        frames[name] = wp.media({
          frame: 'post',
          multiple: true,
          library: {
            type: mtypes
          }
        });
        frames[name].on('insert', function(e) {
          var json, row, imgsrc;
          frames[name].state().get('selection').each(function(file) {
            json = file.toJSON();
            if ($.inArray(json.mime, mtypes) === -1) {
              alert(options.fileNotAllowedError);
              return false;
            }

            if (typeof json.sizes !== 'undefined') {
              imgsrc = typeof json.sizes.thumbnail !== 'undefined' ? json.sizes.thumbnail.url : json.sizes.full.url;
            } else {
              imgsrc = json.icon;
            }
            row = '<tr class="drts-wp-file-row">' + '<td><input name="' + name + '[current][' + json.id + '][check][]" type="checkbox" value="' + json.id + '" checked="checked"></td>' + '<td><img src="' + imgsrc + '" alt="' + json.title + '" /></td>' + '<td>' + json.title + '</td>' + '<td>' + json.filesizeHumanReadable + '</td></tr>';
            if (!table.has('.drts-wp-file-row').length) {
              table.find('tbody').html(row).effect('highlight', {}, 2000);
            } else {
              $(row).appendTo(table.find('tbody')).effect('highlight', {}, 2000);
            }
          });
        });
      }
      frames[name].open();
    });

    $button.closest('form').submit(function() {
      if (options.maxNumFiles && table.find('input[type="checkbox"]:checked').length > options.maxNumFiles) {
        if (options.maxNumFileExceededError) alert(options.maxNumFileExceededError);
        $(this).find('button[type=submit]').prop('disabled', false);
        return false;
      }
    });
    if (options.sortable) {
      table.find('tbody').sortable({
        containment: 'parent',
        axis: 'y'
      });
    }
  };
})(jQuery);