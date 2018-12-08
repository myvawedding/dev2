<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;

class AdvancedSettingsFormHelper
{
    public function help(Application $application, $display, array $values = [], array $options = [], $weight = 99)
    {
        $options += array(
            'name' => 'advanced',
            'designable' => true,
            'cacheable' => true,
        );
        $form = [];
        
        if ($options['designable']) {
            $form += array(
                'css_class' => array(
                    '#title' => __('CSS class', 'directories'),
                    '#type' => 'textfield',
                    '#default_value' => isset($values['css_class']) ? $values['css_class'] : null,
                ),
                'css_id' => array(
                    '#title' => __('CSS ID', 'directories'),
                    '#type' => 'textfield',
                    '#default_value' => isset($values['css_id']) ? $values['css_id'] : null,
                    '#description' => $display->type === 'entity' ? sprintf(
                        __('Available tokens: %s', 'directories'),
                        '%id%'
                    ) : null,
                ),
            );
        }
        
        if ($options['cacheable']) {
            $form['cache'] = $application->System_Util_cacheSettingsForm(
                isset($values['cache']) ? $values['cache'] : null,
                is_array($options['cacheable']) ? $options['cacheable'] : null
            );
        }
        
        if (empty($form)) return;
        
        return array(
            '#tree' => true,
            '#tree_allow_override' => false,
            '#horizontal_children' => true,
            '#weight' => $weight,
            '#element_validate' => array(array(array(__CLASS__, '_filterSettings'), array($options))),
        ) + $form;
    }
    
    public static function filterSettings(array &$settings)
    {
        if (empty($settings['css_class'])) {
            unset($settings['css_class']);
        }
        if (empty($settings['css_id'])) {
            unset($settings['css_id']);
        }
        
        if (empty($settings)) $settings = null;
    }
    
    public static function _filterSettings(Form\Form $form, array &$settings, array $options)
    {
        self::filterSettings($settings);
    }
}