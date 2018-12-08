<?php
namespace SabaiApps\Directories\Component\PhotoSlider\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class PhotoSliderFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected static $_count = 0;
    
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => __('Photo Slider', 'directories-pro'),
            'field_types' => array('wp_image', 'file_image'),
            'default_settings' => array(
                'size' => 'large',
                'effect' => 'slide',
                'show_thumbs' => true,
                'thumbs_columns' => 6,
                'pager' => false,
                'auto' => false,
                'controls' => true,
                'zoom' => true,
                'show_videos' => false,
                'video_field' => null,
                'prepend_videos' => false,
                'num_videos' => 1,
            ),
            'separatable' => false,
            'emptiable' => true, // for when there are only videos to show
        );
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $form = array(
            'size' => array(
                '#title' => __('Image size', 'directories-pro'),
                '#type' => 'select',
                '#options' => $this->_getImageSizeOptions(),
                '#default_value' => $settings['size'],
                '#inline' => true,
            ),
            'show_thumbs' => array(
                '#title' => __('Show thumbnails', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['show_thumbs']),
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[size]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['thumbnail', 'thumbnail_scaled']),
                    ),
                ),
            ),
            'thumbs_columns' => array(
                '#title' => __('Number of thumbnail columns', 'directories-pro'),
                '#type' => 'slider',
                '#min_value' => 2,
                '#max_value' => 12,
                '#default_value' => $settings['thumbs_columns'],
                '#states' => array(
                    'invisible_or' => array(
                        sprintf('select[name="%s[size]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['thumbnail', 'thumbnail_scaled']),
                        sprintf('input[name="%s[show_thumbs]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => false),
                    ),
                ),
            ),
            'effect' => array(
                '#title' => __('Slider effect', 'directories-pro'),
                '#type' => 'select',
                '#options' => $this->_getPhotoSliderEffectOptions(),
                '#default_value' => $settings['effect'],
                '#inline' => true,
            ),
            'pager' => array(
                '#title' => __('Show slide indicators', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['pager']),
            ),
            'auto' => array(
                '#title' => __('Autoplay slides', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['auto']),
            ),
            'zoom' => array(
                '#title' => __('Zoom on click image', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['zoom']),
            ),
            'controls' => array(
                '#title' => __('Show prev/next arrows', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['controls']),
            ),
        );
        if ($video_fields = $this->_application->Entity_Field_options($field->Bundle, ['interface' => 'Field\Type\IVideo', 'empty_value' => ''])) {
            $form += [
                'show_videos' => [
                    '#type' => 'checkbox',
                    '#title' => __('Include videos', 'directories-pro'),
                    '#default_value' => !empty($settings['show_videos']),
                ],
                'video_field' => [
                    '#type' => 'select',
                    '#title' => __('Video field', 'directories-pro'),
                    '#options' => $video_fields,
                    '#default_value' => !empty($settings['video_field']),
                    '#states' => [
                        'visible' => [
                            sprintf('[name="%s"]', $this->_application->Form_FieldName([$parents, 'show_videos'])) => ['type' => 'checked', 'value' => 1],
                        ],
                    ],
                ],
                'prepend_videos' => [
                    '#type' => 'checkbox',
                    '#title' => __('Show videos first', 'directories-pro'),
                    '#default_value' => !empty($settings['prepend_videos']),
                    '#states' => [
                        'visible' => [
                            sprintf('[name="%s"]', $this->_application->Form_FieldName([$parents, 'show_videos'])) => ['type' => 'checked', 'value' => 1],
                        ],
                    ],
                ],
                'num_videos' => [
                    '#type' => 'slider',
                    '#title' => __('Max number of videos', 'directories-pro'),
                    '#default_value' => $settings['num_videos'],
                    '#min_value' => 0,
                    '#max_value' => 10,
                    '#min_text' => __('Unlimited', 'directories-pro'),
                    '#states' => [
                        'visible' => [
                            sprintf('[name="%s"]', $this->_application->Form_FieldName([$parents, 'show_videos'])) => ['type' => 'checked', 'value' => 1],
                        ],
                    ],
                ],
            ];
        }
        return $form;
    }
    
    protected function _getPhotoSliderEffectOptions()
    {
        return [
            'slide' => __('Slide', 'directories-pro'),
            'fade' => __('Fade', 'directories-pro'),
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $videos = $this->_getVideos($settings, $entity);
        if (empty($values) && !$videos) return;

        if (!$field_type_impl = $this->_application->Field_Type($field->getFieldType(), true)) return;
        
        $images = [];
        $options = array(
            'photoslider_class' => 'drts-field-photoslider',
            'photoslider_fade' => $settings['effect'] === 'fade',
            'photoslider_pager' => !empty($settings['pager']),
            'photoslider_auto' => !empty($settings['auto']),
            'photoslider_controls' => !empty($settings['controls']),
            'photoslider_columns' => 1,
            'photoslider_center' => false,
            'photoslider_infinite' => false,
            'photoslider_zoom' => !empty($settings['zoom']),
        );
        foreach (array_keys($values) as $k) {
            if (!$img = $field_type_impl->fieldImageGetUrl($values[$k], $settings['size'])) {
                unset($values[$k]);
                continue;
            }
            
            $images[] = array(
                'title' => $field_type_impl->fieldImageGetTitle($values[$k]),
                'img' => $img,
                'url' => empty($settings['zoom']) ? $field_type_impl->fieldImageGetUrl($values[$k], 'full') : null, 
            );
        }
        if ($videos) {
            $images = empty($settings['prepend_videos']) ? array_merge($images, $videos) : array_merge($videos, $images);
        }
        if (empty($images)) return;
        
        if (!empty($settings['show_thumbs'])
            && $settings['size'] !== 'thumbnail'
            && $settings['size'] !== 'thumbnail_scaled'
        ) {
            $options['photoslider_thumbs'] = [];
            $options['photoslider_thumbs_columns'] = $settings['thumbs_columns'];
            foreach (array_keys($values) as $k) {
                if (!$thumb = $field_type_impl->fieldImageGetUrl($values[$k], 'thumbnail')) {
                    unset($options['photoslider_thumbs']);
                    break;
                }
            
                $options['photoslider_thumbs'][] = array(
                    'img' => $thumb,
                );
            }
            if ($videos) {
                $video_thumbs = [];
                foreach (array_keys($videos) as $k) {
                    $video_thumbs[] = array(
                        'img' => $videos[$k]['thumbnail_url'],
                    );
                }
                if (!empty($settings['prepend_videos'])) {
                    $options['photoslider_thumbs'] = array_merge($video_thumbs, $options['photoslider_thumbs']);
                } else {
                    $options['photoslider_thumbs'] = array_merge($options['photoslider_thumbs'], $video_thumbs);
                }
            }
        }
        
        return $this->_application->PhotoSlider_Slider($images, $options);
    }
    
    protected function _getVideos(array $settings, Entity\Type\IEntity $entity)
    {
        if (empty($settings['show_videos'])
            || empty($settings['video_field'])
            || (!$values = $entity->getFieldValue($settings['video_field']))
        ) return;
        
        $videos = [];
        $num = 0;
        foreach (array_keys($values) as $i) {
            if (empty($values[$i]['thumbnail_url'])) continue;
            
            $videos[] = [
                'type' => $values[$i]['provider'],
                'video_id' => $values[$i]['id'],
                'thumbnail_url' => $values[$i]['thumbnail_url'],
                'title' => isset($values[$i]['title']) ? $values[$i]['title'] : null,
            ];
            if (!empty($settings['num_videos'])
                && $num >= $settings['num_videos']
            ) break;
        }
        return $videos;
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $ret = [
            'size' => [
                'label' => __('Image size', 'directories-pro'),
                'value' => $this->_getImageSizeOptions()[$settings['size']],
            ],
        ];
        if (!in_array($settings['size'], ['thumbnail', 'thumbnail_scaled'])) {
            $ret['show_thumbs'] = [
                'label' => __('Show thumbnails', 'directories-pro'),
                'value' => !empty($settings['show_thumbs']),
                'is_bool' => true,
            ];
            if (!empty($settings['show_thumbs'])) {
                $ret['thumbs_columns'] = [
                    'label' => __('Number of thumbnail columns', 'directories-pro'),
                    'value' => $settings['thumbs_columns'],
                ];
            }
        }
        $ret += [
            'effect' => [
                'label' => __('Slider effect', 'directories-pro'),
                'value' => $this->_getPhotoSliderEffectOptions()[$settings['effect']],
            ],
            'pager' => [
                'label' => __('Show slide indicators', 'directories-pro'),
                'value' => !empty($settings['pager']),
                'is_bool' => true,
            ],
            'auto' => [
                'label' => __('Autoplay slides', 'directories-pro'),
                'value' => !empty($settings['auto']),
                'is_bool' => true,
            ],
            'controls' => [
                'label' => __('Show prev/next arrows', 'directories-pro'),
                'value' => !empty($settings['controls']),
                'is_bool' => true,
            ],
            'show_videos' => [
                'label' => __('Show videos', 'directories-pro'),
                'value' => !empty($settings['show_videos']),
                'is_bool' => true,
            ],
            'video_field' => [
                'label' => __('Video field', 'directories-pro'),
                'value' => isset($settings['video_field']) ? $settings['video_field'] : null,
            ],
            'prepend_videos' => [
                'label' => __('Prepend videos', 'directories-pro'),
                'value' => !empty($settings['prepend_videos']),
                'is_bool' => true,
            ],
            'num_videos' => [
                'label' => __('Max number of videos', 'directories-pro'),
                'value' => isset($settings['num_videos']) ? $settings['num_videos'] : null,
            ],
        ];
        
        return $ret;
    }
}