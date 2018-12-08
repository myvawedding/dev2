<?php
namespace SabaiApps\Directories\Component\DirectoryPro\SystemWidget;

use SabaiApps\Directories\Component\System\Widget\AbstractWidget;
use SabaiApps\Directories\Application;

class FiltersSystemWidget extends AbstractWidget
{    
    protected $_cacheable = false;
    
    protected function _systemWidgetInfo()
    {
        return array(
            'title' => __('Filter Form', 'directories-pro'),
            'summary' => __('Displays a filter form.', 'directories-pro'),
        );
    }
    
    protected function _getWidgetSettings(array $settings)
    {
        $directory_options = [];
        foreach ($this->_application->getModel('Directory', 'Directory')->fetch() as $directory) {
            if (!$this->_application->Directory_Types_impl($directory->type, true)) continue; // make sure the directory type is active
            
            $directory_options[$directory->name] = $directory->getLabel();
        }
        if (empty($directory_options)) return;
        
        $directory_option_keys = array_keys($directory_options);
        return array(   
            'directory' => array(
                '#title' => __('Select directory', 'directories-pro'),
                '#options' => $directory_options,
                '#type' => count($directory_options) <= 1 ? 'hidden' : 'select',
                '#default_value' => array_shift($directory_option_keys),
            ),
            'auto_submit' => array(
                '#title' => __('Auto submit filter form', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['auto_submit']),
            ),
        );
    }
    
    protected function _getWidgetContent(array $settings)
    {       
        if (!isset($settings['directory'])
            || !strlen($settings['directory'])
            || !isset($GLOBALS['drts_view_entites_context'])
            || !isset($GLOBALS['drts_view_entites_context']['bundle'])
            || $settings['directory'] !== $GLOBALS['drts_view_entites_context']['bundle']->group
        ) return;
        
        $context = $GLOBALS['drts_view_entites_context'];
        $form = $this->_application->View_FilterForm(
            $context['bundle']->name,
            array(
                'container' => $context['container'],
                'filters' => $context['filters'], 
                'values' => $context['filter_values'],
                'url' => $this->_application->Url($context['route'], $context['url_params']),
                'push_state' => true,
                'query' => $context['query'],
            )
        );
        if (empty($form['#filters'])) return;
        
        $form['#js_ready'][] = 'DRTS.init("#__FORM_ID__");';
        
        $class = 'drts-view-filter-form-external';
        if (empty($settings['auto_submit'])) {
            $class .= ' drts-view-filter-form-manual';
        }
        return '<div id="' . substr($context['container'], 1) . '-view-filter-form' . '" class="' . $class . '">'
            .  $this->_application->View_FilterForm_render($this->_application->Form_Build($form, true, $context['filter_values']), null, true)
            . '</div>';
    }
}