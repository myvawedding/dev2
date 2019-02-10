<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;

class UtilHelper
{   
    public function iconSizeOptions(Application $application)
    {
        return $application->Filter(
            'system_icon_size_options',
            [
                'sm' => __('Small', 'directories'),
                '' => __('Medium', 'directories'),
            ]
        );
    }
    
    public function iconSettingsForm(Application $application, Entity\Model\Bundle $bundle, array $settings, array $parents = [], $weight = null, $horizontal = true)
    {
        if (empty($bundle->info['entity_icon'])
            && empty($bundle->info['entity_image'])
        ) return [];

        $form = [
            'icon' => [
                '#type' => 'checkbox',
                '#title' => __('Show icon', 'directories'),
                '#default_value' => !empty($settings['icon']),
                '#weight' => isset($weight) ? $weight : null,
                '#horizontal' => $horizontal,
            ],
            'icon_settings' => [
                '#tree' => true,
                '#element_validate' => [
                    function (Form\Form $form, &$value) use ($bundle) {
                        $value['is_image'] = !empty($bundle->info['entity_image']);
                    }
                ],
                '#weight' => isset($weight) ? ++$weight : null,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['icon']))) => [
                            'type' => 'checked', 
                            'value' => true,
                        ],
                    ],
                ],
                'size' => [
                    '#type' => 'select',
                    '#title' => __('Icon size', 'directories'),
                    '#default_value' => isset($settings['icon_settings']['size']) ? $settings['icon_settings']['size'] : null,
                    '#options' => $application->System_Util_iconSizeOptions(),
                    '#horizontal' => $horizontal,
                ],
                'fallback' => [
                    '#type' => 'checkbox',
                    '#title' => __('Fallback to default icon', 'directories'),
                    '#default_value' => !empty($settings['icon_settings']['fallback']),
                    '#states' => [
                        'invisible' => [
                            sprintf('[name="%s"]', $application->Form_FieldName(array_merge($parents, ['icon_settings', 'field']))) => [
                                'value' => '',
                            ],
                        ],
                    ],
                    '#horizontal' => $horizontal,
                ],
            ],
        ];

        if (empty($bundle->info['entity_image'])) {
            // Add color options
            $form['icon_settings']['color'] = $this->iconColorSettingsForm(
                $application,
                $bundle,
                isset($settings['icon_settings']['color']) && is_array($settings['icon_settings']['color']) ? $settings['icon_settings']['color'] : [],
                array_merge($parents, ['icon_settings', 'color']),
                $horizontal
            );
        }
        
        return $form;
    }

    public function iconColorSettingsForm(Application $application, Entity\Model\Bundle $bundle, array $settings, array $parents = [], $horizontal = true)
    {
        return [
            'type' => [
                '#type' => 'select',
                '#title' => __('Icon color', 'directories'),
                '#default_value' => isset($settings['type']) ? $settings['type'] : '',
                '#options' => $application->Entity_Field_options($bundle, [
                    'interface' => 'Field\Type\ColorType',
                    'prefix' => __('Field - ', 'directories'),
                ]) + [
                    '_custom' => __('Choose a color', 'directories'),
                    '' => __('Default', 'directories')
                ],
                '#horizontal' => $horizontal,
            ],
            'custom' => [
                '#type' => 'colorpicker',
                '#default_value' => isset($settings['custom']) ? $settings['custom'] : null,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($parents, ['type']))) => [
                            'value' => '_custom',
                        ],
                    ],
                ],
                '#horizontal' => $horizontal,
            ],
        ];
    }
    
    public function iconSettingsToPermalinkOptions(Application $application, Entity\Model\Bundle $bundle, array $iconSettings)
    {
        $options = [
            'icon' => empty($bundle->info['entity_image']) ? $bundle->info['entity_icon'] : $bundle->info['entity_image'],
            'icon_is_image' => !empty($bundle->info['entity_image']),
            'icon_size' => $iconSettings['size'],
            'icon_fallback' => !empty($iconSettings['fallback']),
        ];
        if (!empty($iconSettings['color']['type'])) {
            $options['icon_color'] = $iconSettings['color']['type'] === '_custom'
                ? $iconSettings['color']['custom']
                : $iconSettings['color']['type'];
        }
        return $options;
    }
    
    public function cacheSettingsForm(Application $applicatoin, $value = null, array $possibleValues = null)
    {
        $options = ['' => __('No cache', 'directories')];
        foreach ([1, 2, 5, 10, 30] as $min) {
            $options[$min * 60] = sprintf(_n('%d minute', '%d minutes', $min, 'directories'), $min);
        }
        foreach ([1, 2, 5, 10] as $hour) {
            $options[$hour * 3600] = sprintf(_n('%d hour', '%d hours', $hour, 'directories'), $hour);
        }
        foreach ([1, 2, 5, 10, 30] as $day) {
            $options[$day * 86400] = sprintf(_n('%d day', '%d days', $day, 'directories'), $day);
        }
        if (isset($possibleValues)) {
            $options = array_intersect_key($options, array_flip($possibleValues));
        }
            
        return [
            '#title' => __('Cache output', 'directories'),
            '#type' => 'select',
            '#options' => $options,
            '#default_value' => $value,
            '#horizontal' => true,
        ];
    }
    
    public function colorOptions(Application $application, $buttons = false, $includeLink = false)
    {
        $ret = [];
        $colors = ['primary', 'secondary', 'info' , 'success', 'warning', 'danger', 'light', 'dark'];
        if ($buttons) {
            $btn_class = DRTS_BS_PREFIX . 'btn';
            foreach ($colors as $value) {
                $ret[$value] = sprintf('<button class="%1$s %1$s-sm %1$s-%2$s" onclick="return false;"> </button>', $btn_class, $value);
                $ret['outline-' . $value] = sprintf('<button class="%1$s %1$s-sm %1$s-outline-%2$s" onclick="return false;"> </button>', $btn_class, $value);
            }
            if ($includeLink) {
                $ret['link'] = __('Link', 'directories');
            }
        } else {
            foreach ($colors as $value) {
                $ret[$value] = '<span class="' . DRTS_BS_PREFIX. 'badge ' . DRTS_BS_PREFIX . 'badge-' . $value . '">&nbsp;</span>';
            }
        }
        
        return $ret;
    }
    
        
    public function colorSettingsForm(Application $application, $value = null, array $parents = [], $title = null)
    {
        return [
            'type' => array(
                '#type' => 'select',
                '#title' => isset($title) ? $title : __('Color', 'directories'),
                '#default_value' => isset($value['type']) ? $value['type'] : null,
                '#options' => ['' => __('Default', 'directories'), 'custom' => __('Custom', 'directories')],
                '#horizontal' => true,
            ),
            'value' => [
                '#type' => 'colorpicker',
                '#default_value' => isset($value['value']) ? $value['value'] : null,
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($parents, ['type']))) => array('value' => 'custom'),
                    ),
                ),
            ],
        ];
    }

    public function strToBytes(Application $application, $str)
    {
        if (is_int($str)) return $str;

        $suffix = strtoupper(substr($str, -1));
        if (!in_array($suffix, ['P','T','G','M','K'])) return (int)$str;

        $value = (int)substr($str, 0, -1);
        switch ($suffix) {
            case 'P':
                $value *= 1024;
            case 'T':
                $value *= 1024;
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
                break;
        }
        return $value;
    }

    public function bytesToStr(Application $application, $bytes, $decimals = 1)
    {
        $suffix = 'B';
        $suffix_list = ['P','T','G','M','K'];
        while ($bytes > 1024
            && ($suffix = array_pop($suffix_list))
        ) {
            $bytes /= 1024;
        }
        return round($bytes, $decimals) . $suffix;
    }
}