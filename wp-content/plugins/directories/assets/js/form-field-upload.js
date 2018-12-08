'use strict';

(function($) {
  DRTS.Form.field.upload = function(options) {
    options = $.extend({
      selector: '',
      maxNumFiles: 0,
      maxNumFileExceededError: '',
      inputName: 'files',
      sortable: true,
      formData: {}
    }, options);

    var $uploader = $(options.selector);
    if (!$uploader.length) return;

    var $container = $uploader.closest('.drts-form-upload-container'),
      $progress = $container.find('.' + DRTS.bsPrefix + 'progress'),
      $progressBar = $progress.find('.' + DRTS.bsPrefix + 'progress-bar'),
      numFilesUploaded = 0;

    $uploader.fileupload({
      url: options.url,
      dataType: 'json',
      paramName: 'drts_form_upload',
      formData: options.formData,
      singleFileUploads: true,
      //forceIframeTransport: true,
      submit: function submit(event, data) {
        if (options.maxNumFiles && numFilesUploaded + data.files.length > options.maxNumFiles) {
          if (options.maxNumFileExceededError) alert(options.maxNumFileExceededError);
          return false;
        }
        $progressBar.attr('aria-valuenow', 0).css('width', '0%').text('0%');
        $progress.show();
      },
      fail: function fail(e, data) {
        $progress.hide();
        DRTS.flash(data.result.error, 'danger');
      },
      done: function done(e, data) {
        if (data.result.error) {
          $progress.hide();
          DRTS.flash(data.result.error, 'danger');
          return;
        }
        numFilesUploaded += data.result.files.length;
        var table = $container.find('.drts-form-upload-current').find('table');
        DRTS.ajaxLoader(null, false, $container);
        $.each(data.result.files, function(index, file) {
          var new_row = $('<tr class="drts-form-upload-row"/>'),
            check = $('<input type="checkbox" checked="checked">').attr('name', options.inputName + '[current][' + file.id + '][check][]').val(file.id),
            name = $('<input class="' + DRTS.bsPrefix + 'form-control" type="text" checked="checked">').attr('name', options.inputName + '[current][' + file.id + '][name]').val(file.title),
            download = $('<a target="_blank"/>').attr('href', file.url).html('<i class="fas fa-lg fa-download"></i>');
          $('<td/>').html(check).appendTo(new_row);
          if (file.thumbnail) {
            $('<td/>').html($('<img/>').attr('src', file.thumbnail)).appendTo(new_row);
          } else {
            $('<td/>').html($('<i/>').attr('class', file.icon)).appendTo(new_row);
          }
          $('<td/>').html(name).appendTo(new_row);
          $('<td/>').text(file.size_hr).appendTo(new_row);
          $('<td/>').html(download).appendTo(new_row);

          if (!table.has('.drts-form-upload-row').length) {
            table.find('tbody').html(new_row).effect('highlight', {}, 2000);
          } else {
            $(new_row).appendTo(table.find('tbody')).effect('highlight', {}, 2000);
          }
        });
        DRTS.ajaxLoader(null, true, $container);
        $progress.hide();
        if (options.sortable) {
          DRTS.init(table.find('tbody').sortable('destroy').sortable({
            containment: 'parent',
            axis: 'y'
          }).parent()); // reset table
        }
      },
      progressall: function progressall(e, data) {
        var progress = parseInt(data.loaded / data.total * 100, 10);
        $progressBar.attr('aria-valuenow', progress).css('width', progress + '%').text(progress + '%');
      }
    });
    $uploader.closest('form').submit(function() {
      if (options.maxNumFiles && $container.find('.drts-form-upload-current tbody input[type="checkbox"]:checked').length > options.maxNumFiles) {
        if (options.maxNumFileExceededError) alert(options.maxNumFileExceededError);
        return false;
      }
    });
    if (options.sortable) {
      $container.find('.drts-form-upload-current tbody').sortable({
        containment: 'parent',
        axis: 'y'
      });
    }
  };
})(jQuery);