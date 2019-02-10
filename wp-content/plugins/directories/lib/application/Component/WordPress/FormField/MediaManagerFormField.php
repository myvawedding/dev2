<?php
namespace SabaiApps\Directories\Component\WordPress\FormField;

use SabaiApps\Directories\Component\Form;

class MediaManagerFormField extends Form\Field\FieldsetField
{
    protected static $_uploadFields = [], $_imageMimeTypes = array('image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/tiff', 'image/ico');

    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        $max_file_size_str = $this->_application->System_Util_bytesToStr(wp_max_upload_size());

        // Define element settings
        $data = array(
            '#tree' => true,
            '#multiple' => !empty($data['#multiple']),
            '#children' => [],
            '#max_num_files' => (int)@$data['#max_num_files'],
            '#mime_types' => [],
        ) + $data;

        if (!empty($data['#allow_only_images'])) {
            $data['#mime_types'] = self::$_imageMimeTypes;
        } else {
            if (!empty($data['#wp_allowed_extensions'])) {
                $allowed_extensions = $data['#wp_allowed_extensions'];
            } elseif (!empty($data['#allowed_extensions'])) {
                $allowed_extensions = $data['#allowed_extensions'];
            }
            if (!empty($allowed_extensions)) {
                $allowed_mime_types = get_allowed_mime_types();
                foreach ($allowed_extensions as $ext) {
                    if (isset($allowed_mime_types[$ext])) {
                        $data['#mime_types'][] = $allowed_mime_types[$ext];
                    }
                }
            } else {
                $data['#mime_types'] = array_merge(self::$_imageMimeTypes, array('text/plain', 'application/zip', 'application/pdf'));
            }
        }

        // Add current file selection fields
        $current_file_options = $data['#_current_files'] = [];
        $row_attr = isset($data['#row_attributes']) ? $data['#row_attributes'] : [];
        if (!empty($data['#default_value'])) {
            if (is_array($data['#default_value'])) {
                if (isset($data['#default_value']['current'])) {
                    // values from previous submit
                    $data['#default_value'] = array_keys($data['#default_value']['current']);
                } else {
                    $new_default_values = [];
                    foreach ($data['#default_value'] as $default_value) {
                        $new_default_values[] = is_array($default_value) ? $default_value['attachment_id'] : $default_value;
                    }
                    $data['#default_value'] = $new_default_values;
                }
            } else {
                $data['#default_value'] = array($data['#default_value']);
            }
            if (!empty($data['#default_value'])) {
                foreach ($data['#default_value'] as $attachment_id) {
                    if (!$src = wp_get_attachment_image_src($attachment_id, 'thumbnail', true)) {
                        continue;
                    }
                    $current_file_options[$attachment_id] = array(
                        'name' => '<a href="' . wp_get_attachment_url($attachment_id) . '">' . esc_html(get_the_title($attachment_id)) . '</a>',
                        'icon' => sprintf('<img src="%s" width="%d" height="%d" alt="" style="max-height:none !important" />', $src[0], $src[1], $src[2]),
                    );
                    if ($filesize = @filesize(get_attached_file($attachment_id))) {
                        $current_file_options[$attachment_id]['size'] = size_format($filesize);
                    }
                    $data['#_current_files'][$attachment_id] = $attachment_id;
                    if (!isset($row_attr[$attachment_id]['@row']['class'])) {
                        $row_attr[$attachment_id]['@row']['class'] = 'drts-wp-file-row';
                    } else {
                        $row_attr[$attachment_id]['@row']['class'] .= ' drts-wp-file-row'; 
                    }
                }
                if (!empty($current_file_options)) {
                    $_current_file_options = [];
                    // Reorder options as it was stored
                    foreach ($data['#default_value'] as $file_id) {
                        if (isset($current_file_options[$file_id])) {
                            $_current_file_options[$file_id] = $current_file_options[$file_id];
                        }
                    }
                    $current_file_options = $_current_file_options;
                }
            }
        }

        $current_file_element = array(
            '#type' => 'grid',
            '#class' => 'drts-form-upload-current drts-wp-upload-current drts-data-table',
            '#empty_text' => isset($data['#empty_text']) ? $data['#empty_text'] : __('There are currently no files uploaded.', 'directories'),
            '#column_attributes' => array('thumbnail' => array('style' => 'width:25%;')),
            '#row_attributes' => $row_attr,
            '#disable_template_override' => true,
        );
        $current_file_element['#children'][0] = array(
            'check' => array(
                '#type' =>  'checkbox',
                '#title' => '',
                '#switch' => false,
            ) + $form->defaultFieldSettings(),
            'icon' => array(
                '#type' => 'markup',
                '#title' => '',
            ) + $form->defaultFieldSettings(),
            'name' => array(
                '#type' => 'markup',
                '#title' => __('File Name', 'directories'),
            ) + $form->defaultFieldSettings(),
            'size' => array(
                '#type' => 'item',
                '#title' => __('Size', 'directories'),
            ) + $form->defaultFieldSettings(),
        );
        if (!empty($current_file_options)) {
            foreach ($current_file_options as $current_file_id => $current_file_option) {
                $current_file_element['#default_value'][$current_file_id] = $current_file_option +array(
                    'check' => true,
                );
            }
        }
        $data['#children'][0]['current'] = $current_file_element;
        if (empty($data['#allow_only_images'])) {
            $button_label = __('Add File', 'directories');
            $button_icon = 'fas fa-file fa-fw';
        } else {
            $button_label = __('Add Image', 'directories');
            $button_icon = 'fas fa-image fa-fw';
        }
        $data['#children'][0]['button'] = array(
            '#type' => 'item',
            '#markup' => sprintf(
                '<button id="%1$s" class="%2$sbtn %2$sbtn-outline-secondary %2$sbtn-sm drts-wp-media-manager" data-input-name="%3$s" data-mime-types="%4$s"><i class="%5$s"></i> %6$s</button>',
                $id = $form->getFieldId($name) . '-btn',
                DRTS_BS_PREFIX,
                $name,
                implode(',', $data['#mime_types']),
                $button_icon,
                $this->_application->H($button_label)
            ),
            '#description' => !empty($data['#max_num_files'])
                ? sprintf(__('Max number of files %d, maximum file size %s.', 'directories'), $data['#max_num_files'], $max_file_size_str)
                : sprintf(__('Max file size %s.', 'directories'), $max_file_size_str),
        );

        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        
        self::$_uploadFields[$form->settings['#id']][$id] = array(
            'name' => $name,
            'max_num_files' => $data['#max_num_files'],
            'sortable' => !empty($data['#sortable']),
        );
        $data['#sortable'] = false;

        parent::formFieldInit($name, $data, $form);
    }

    public function formFieldSubmit(&$value, array &$data, Form\Form $form)
    {
        $_values = [];

        // Any current file selected?
        if (!empty($value['current'])) {
            foreach ($value['current'] as $file_id => $file_info) {
                if (empty($file_info['check'][0])) {
                    continue;
                }
                $_values[] = $file_id;

                if (!$data['#multiple']) break;
            }
        }

        $value = [];
        if (!empty($_values)) {
            if (empty($data['#multiple'])) {
                $value = array_slice($_values, 0, 1, true);
            } else {
                $value = $_values;
            }
        }

        if (empty($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('File must be uploaded.', 'directories'), $data);
            }
        } else {
            if ($data['#max_num_files'] && count($value) > $data['#max_num_files']) {
                $form->setError(sprintf(__('You may not upload more than %d files.', 'directories'), $data['#max_num_files']), $data['#name'] . '[current]');
            }
        }
    }

    public function preRenderCallback($form)
    {
        wp_enqueue_media();
        $this->_application->getPlatform()
            ->loadJqueryUiJs(array('effects-highlight'))
            ->addJsFile('wordpress-mediamanager.min.js', 'drts-wordpress-mediamanager', array('jquery-ui-sortable', 'drts'));
        
        $js = [];
        foreach (self::$_uploadFields[$form->settings['#id']] as $upload_id => $upload) {
            $js[] = sprintf('DRTS.WordPress.mediamanager({
    selector: "#%1$s",
    maxNumFiles: %2$d,
    sortable: %3$s,
    maxNumFileExceededError: "%4$s",
    fileNotAllowedError: "%5$s"
});',
                $this->_application->H($upload_id),
                $upload['max_num_files'],
                $upload['sortable'] ? 'true' : 'false',
                sprintf(__('You may not upload more than %d files', 'directories'), $upload['max_num_files']),
                __('One or more of the selected files are not allowed.', 'directories')
            );
        }

        if (empty($js)) return;

        $form->settings['#js_ready'][] = implode(PHP_EOL, $js);

        // Fix for Enfold theme not loading media manager JS files
        add_filter('avf_enqueue_wp_mediaelement', '__return_true');
    }

}