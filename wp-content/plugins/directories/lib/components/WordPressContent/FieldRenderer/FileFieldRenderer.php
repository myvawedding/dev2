<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class FileFieldRenderer extends Field\Renderer\AbstractFileRenderer
{    
    protected $_fieldTypes = array('wp_file');

    protected function _fieldRendererInfo()
    {
        $info = parent::_fieldRendererInfo();
        $info['default_settings'] += [
            'style' => 'light',
            'tracklist' => true,
            'tracknumbers' => true,
        ];
        return $info;
    }

    public function fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['allowed_files'])
            || !in_array($field_settings['allowed_files'], ['video', 'audio'])
        ) return;

        return [
            'style' => [
                '#type' => 'select',
                '#title' => __('Audio/Video player style', '-sabia_plugin_name-'),
                '#options' => [
                    'light' => __('Light', 'color', '-sabia_plugin_name-'),
                    'dark' => __('Dark', 'color', '-sabia_plugin_name-'),
                ],
                '#default_value' => $settings['style'],
            ],
            'tracklist' => [
                '#type' => 'checkbox',
                '#title' => __('Show entries in play list', '-sabia_plugin_name-'),
                '#default_value' => !empty($settings['tracklist']),
            ],
            'tracknumbers' => [
                '#type' => 'checkbox',
                '#title' => __('Show numbers next to entries in play list', '-sabia_plugin_name-'),
                '#default_value' => !empty($settings['tracknumbers']),
                '#states' => [
                    'visible' => [
                        sprintf(
                            'input[name="%s"]',
                            $this->_application->Form_FieldName(array_merge($parents, ['tracklist']))
                        ) => [
                            'type' => 'checked',
                            'value' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function _getFileLink(Field\IField $field, array $settings, $value, Entity\Type\IEntity $entity)
    {
        return wp_get_attachment_link($value['attachment_id'], 'thumbnail', false, true, get_the_title($value['attachment_id']));
    }
    
    protected function _getFileExtension(Field\IField $field, array $settings, $value, Entity\Type\IEntity $entity)
    {
        $file_path = get_attached_file($value['attachment_id']);
        $ext_and_mime_type = wp_check_filetype(basename($file_path));
        
        return $ext_and_mime_type['ext'];
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['allowed_files'])
            || !in_array($field_settings['allowed_files'], ['video', 'audio'])
        ) {
            return parent::_fieldRendererRenderField($field, $settings, $entity, $values, $more);
        }
        $ids = [];
        foreach (array_keys($values) as $k) {
            $ids[] = $values[$k]['attachment_id'];
        }
        $shortcode = sprintf(
            '[playlist style="%s" type="%s" ids="%s" tracklist="%d" tracknumbers="%d"]',
            !in_array($settings['style'], ['light', 'dark']) ? 'light' : $settings['style'],
            $field_settings['allowed_files'],
            implode(',', $ids),
            !empty($settings['tracklist']),
            !empty($settings['tracknumbers'])
        );
        return do_shortcode($shortcode);
    }
}