<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Display\Model\Display;

class VisibilitySettingsFormHelper
{
    public function help(Application $application, Display $display, $element, array $values = [], array $options = [], $weight = 100)
    {
        $options += array(
            'name' => 'visibility',
            'parent' => null,
            'globalable' => false,
        );
        
        $form = [];
        if ($options['parent']) {
            $form += array(
                'hide_on_parent' => array(
                    '#title' => __('Hide on parent content page', 'directories'),
                    '#type' => 'checkbox',
                    '#default_value' => !empty($values['hide_on_parent']),
                ),
            );
        }
        
        if ($options['globalable']) {
            if ($display->type === 'entity') {
                $form['globalize'] = array(
                    '#type' => 'checkbox',
                    '#title' => __('Add rendered content to global scope', 'directories'),
                    '#default_value' => !empty($values['globalize']),
                    '#weight' => 99,
                );
                $form['globalize_remove'] = array(
                    '#type' => 'checkbox',
                    '#title' => __('Remove rendered content from display', 'directories'),
                    '#default_value' => !empty($values['globalize_remove']),
                    '#weight' => 99,
                    '#states' => array(
                        'visible' => array(
                            'input[name="' . $options['name'] . '[globalize]"]' => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                );
            }
        }
        
        $form = $application->Filter('display_visibility_settings_form', $form, array($display, $element, $values, $options));
        
        if (!empty($form)) {
            $form = array(
                '#tree' => true,
                '#tree_allow_override' => false,
                '#horizontal_children' => true,
                '#weight' => $weight,
                '#element_validate' => array(array(array(__CLASS__, '_filterSettings'), array($options))),
            ) + $form;
        }
        
        return $form;
    }
    
    public static function filterSettings(array &$settings)
    {
        if (empty($settings['author_only'])) {
            unset($settings['author_only']);
        }
        if (empty($settings['hide_on_parent'])) {
            unset($settings['hide_on_parent']);
        }
        
        if (empty($settings)) $settings = null;
    }
    
    public static function _filterSettings(Form\Form $form, array &$settings, array $options)
    {
        self::filterSettings($settings);
    }
}