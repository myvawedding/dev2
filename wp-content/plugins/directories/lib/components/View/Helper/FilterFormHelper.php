<?php
namespace SabaiApps\Directories\Component\View\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\View\Model;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Display\Element\AbstractElement;

class FilterFormHelper
{    
    public function help(Application $application, $bundleName, array $options = [])
    {
        if (!$bundle = $application->Entity_Bundle($bundleName)) {
            throw new Exception\RuntimeException('Invalid bundle: ' . $bundleName);
        }
        
        if (!empty($bundle->info['is_taxonomy'])
            || empty($bundle->info['public'])
        ) return;
        
        $filters = $application->Filter(
            'view_filter_form_filters',
            isset($options['filters'])
                ? $options['filters']
                : $application->getModel('Filter', 'View')->bundleName_is($bundleName)->fetch()->with('Field', 'FieldConfig')->getArray(null, 'name'),
            array($bundle)
        );
        if (empty($filters)) return;
        
        $options += array(
            'url' => null,
            'values' => null,
            'container' => null,
            'target' => '.drts-view-entities-container',
            'push_state' => false,
            'submit_btn_label' => null,
            'submit_btn_type' => 'primary',
            'submit_btn_block' => false,
            'query' => null,
            'current' => null,
            'btn_label' => __('Apply Filters', 'directories'),
            'btn_size' => 'lg',
            'btn_color' => ($search_btn_color = $application->getComponent($bundle->component)->getConfig('search', $bundle->type, '_display', 'btn_color')) ? $search_btn_color : 'primary',
        );

        $form = array(
            '#class' => 'drts-view-filter-form',
            '#token' => false,
            '#build_id' => false,
            '#inherits' => array('entity_filter_form'),
            '#bundle' => $bundle,
            '#js_ready_fallback' => true,
            '#js_ready' => array('DRTS.View.filterForm("#__FORM_ID__");'),
            '#filters' => [],
            '#hidden_params' => [], // hidden values converted from URL params
        );
        if (isset($options['container'])) {
            $form['#attributes'] = array(
                'data-entities-container' => $options['container'],
                'data-entities-target' => $options['target'],
                'data-push-state' => (!defined('DRTS_FIX_URI_TOO_LONG') || !DRTS_FIX_URI_TOO_LONG) && !empty($options['push_state']) ? 1 : 0,
            );
        }
        
        // Convert url params to hidden values, mainly used to unset search form values
        if (isset($options['url'])) {
            $url = $application->Url($options['url']);
            unset($url->params['sort'], $url->params['view'], $url->params['num']);
            $this->_addHiddenValues($form, $url->params);
            $form['#hidden_params'] = array_keys($url->params);
            $url->params = [];
            $form['#action'] = $url;
        }
        
        // Add "filter" parameter required to invoke filters
        $form['filter'] = array(
            '#type' => 'hidden',
            '#value' => 1,
        );
        $form['#hidden_params'][] = 'filter';
        
        foreach ($filters as $filter_name => $filter) {
            if (!$field = $filter->getField()) {
                $filter->markRemoved()->commit();
                continue;
            }
  
            if ($field->getFieldData('disabled')) continue;

            if ($filter_form = $this->_getField(
                $application,
                $filter,
                isset($options['values'][$filter_name]) ? $options['values'][$filter_name] : null,
                $options['query'],
                isset($options['current'][$filter_name]) ? $options['current'][$filter_name] : null
            )) {
                $form[$filter_name] = $filter_form;
                $form['#filters'][$filter->type][$filter_name] = $filter;
            }
        }

        if (!empty($form['#filters'])) {
            $application->getPlatform()->addJsFile('view-filter-form.min.js', 'drts-view-filter-form', array('drts'));
        }

        $buttons = [
            [
                '#btn_label' => $options['btn_label'],
                '#btn_size' => $options['btn_size'],
                '#btn_color' => $options['btn_color'],
                '#attributes' => array('class' => DRTS_BS_PREFIX . 'btn-block'),
            ],
        ];
        $form[Form\FormComponent::FORM_SUBMIT_BUTTON_NAME] = $application->Form_SubmitButtons($buttons, ['margin' => DRTS_BS_PREFIX . 'mt-0 ' . DRTS_BS_PREFIX . 'mt-sm-3']);

        return $form;
    }
    
    protected function _addHiddenValues(&$form, $params)
    {
        foreach (array_keys($params) as $key) {
            if (is_array($params[$key])) {
                $form[$key] = array(
                    '#tree' => true,
                );
                $this->_addHiddenValues($form[$key], $params[$key]);
            } else {
                $form[$key] = array(
                    '#type' => 'hidden',
                    '#value' => $params[$key],
                );
            }
        }
    }
    
    public function render(Application $application, Form\Form $form, $columns = null, $isExternal = false)
    {
        if ($isExternal) $form->settings['#attributes']['data-external'] = 1;
        $form = $form->render();
        $display = $this->getDisplay($application, $form->settings['#bundle']);
        
        // Extract headers from display elements
        $headers = [];
        foreach (array_keys($display['elements']) as $element_id) {
            $element =& $display['elements'][$element_id];
            if (isset($element['heading']['label'])) {
                $headers[$element_id] = $application->Display_ElementLabelSettingsForm_label($element['heading'], AbstractElement::stringId($element['name'], 'label', $element['element_id']));
                unset($element['heading']);
            } elseif (!empty($element['settings']['label_as_heading'])) {
                $headers[$element_id] = $application->Display_ElementLabelSettingsForm_label(
                    $element['settings'],
                    AbstractElement::stringId($element['name'], 'label', $element['element_id']),
                    empty($element['settings']['field_name']) || (!$field = $application->Entity_Field($form->settings['#bundle'], $element['settings']['field_name'])) ? '' : $field->getFieldLabel()
                );
                $element['settings']['label'] = 'none';
            }
        }

        $options = [
            'tag' => null,
            'html_as_array' => true,
        ];
        $rendered = $application->Display_Render($form->settings['#bundle'], $display, $form, $options);
        if (!$rendered || !$rendered['html']) return '';
        
        $html = '<div' . $application->Attr($rendered['attr']) . '>';
        if (!isset($columns)) $columns = 3;
        
        // Do not group external filter form initially 
        $card_group_class = $isExternal ? DRTS_BS_PREFIX . 'card-group-none' : DRTS_BS_PREFIX . 'card-group';
        
        foreach ($application->SliceArray($rendered['html'], $columns, false) as $row) {
            $html .= '<div class="' . $card_group_class . '">';
            foreach (array_keys($row) as $element_id) {
                $html .= '<div class="' . DRTS_BS_PREFIX . 'card">';
                if (isset($headers[$element_id])) {
                    $html .= '<div class="' . DRTS_BS_PREFIX . 'card-header">' . $headers[$element_id] . '</div>';
                }
                $html .= '<div class="' . DRTS_BS_PREFIX . 'card-body">' . $row[$element_id] . '</div></div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Manually render hidden param values here since they were added dynamically and are not rendered by the display
        $hidden_values = '';
        foreach ($form->settings['#hidden_params'] as $param) {
            $hidden_values .= $form->getHtml($param);
        }
        
        return implode(PHP_EOL, array(
            $form->getHeaderHtml(),
            $form->getFormTag(),
            $html,
            $form->getHtml(Form\FormComponent::FORM_SUBMIT_BUTTON_NAME),
            $form->getHiddenHtml(),
            $hidden_values,
            '</form>',
            $rendered['js'],
            $form->getJsHtml(),
        ));
    }
    
    public function getDisplay(Application $application, Entity\Model\Bundle $bundle, $name = 'default', $useCache = true, $checkFields = false)
    {
        if (!$display = $application->Display_Display($bundle->name, $name, 'filters', $useCache)) {
            $elements = [];
            $field_types = $application->Field_Types();
            foreach ($application->getModel('Filter', 'View')->bundleName_is($bundle->name)->fetch()->with('Field', 'FieldConfig') as $filter) {
                if ((!$ifilter = $application->Field_Filters_impl($filter->type, true))
                    || (!$field = $filter->getField())
                    || !isset($field_types[$field->getFieldType()])
                    || empty($field_types[$field->getFieldType()]['filters'])
                ) continue;
                    
                $elements[] = array(
                    'name' => 'view_filter_' . $field->getFieldName(),
                    'weight' => (int)@$filter->data['weight'],
                    'system' => !$filter->isCustomFilter(),
                    'data' => array(
                        'title' => $field->getFieldLabel(),
                        'settings' => array('filter' => $filter->type, 'filter_name' => $filter->name)
                    ),
                );
            }
            $application->Display_Create($bundle, 'filters', $name, array(
                'system' => true, 
                'elements' => $elements
            ));
            
            // Attempt to fetch the display again
            if (!$display = $application->Display_Display($bundle->name, $name, 'filters', false)) {
                throw new Exception\RuntimeException('Failed loading filters display for ' . $bundle->name);
            }
        } else {
            if ($checkFields) {
                $reload_required = false;
                
                // Get current element names in display
                $elements = [];
                foreach (array_keys($display['elements']) as $element_id) {
                    $this->_getRecursiveElements($display['elements'][$element_id], $elements);
                }
                // Create field element if the element does not eixst in display
                $elements_required = $this->_getValidDisplayElements($application, $bundle);
                foreach (array_keys($elements_required) as $filter_name) {
                    if (!isset($elements[$filter_name])) {
                        // The field element does not exist in display, so create it
                        $application->Display_Create_element($bundle, $display['name'], $elements_required[$filter_name], 'filters');
                        
                        $reload_required = true;
                    } else {
                        unset($elements[$filter_name]);
                    }
                }
                // Remove elements and its filters that are no longer valid. This happens for example when the field associated with a filter has been deleted.
                if (!empty($elements)) {
                    foreach ($elements as $element_id) {
                        // Do not notify display element implementation since the field associated with the element most likely does not exist
                        // and the implementation as well no longer exists which will cause a runtime exception to be thrown.
                        // It is safe to not notify since the filter associated has been removed already. 
                        $notify = false; 
                        $application->Display_AdminElement_delete($bundle, $element_id, $notify);
                        unset($display['elements'][$element_id]);
                        
                        $reload_required = true;
                    }
                }
                
                // Attempt to fetch the display again if reload requried
                if ($reload_required) { 
                    if (!$display = $application->Display_Display($bundle->name, $name, 'filters', false)) {
                        throw new Exception\RuntimeException('Failed loading filter form display for ' . $bundle->name);
                    }
                }
            }
        }
        
        return $display;
    }
    
    protected function _getRecursiveElements($element, &$names)
    {
        if ($element['type'] === 'field') {
            $names[$element['settings']['filter_name']] = $element['id'];
        }
        if (!empty($element['children'])) {
            foreach (array_keys($element['children']) as $element_id) {
                $this->_getRecursiveElements($element['children'][$element_id], $names);
            }
        }
    }
    
    protected function _getValidDisplayElements(Application $application, Entity\Model\Bundle $bundle)
    {
        $elements = [];
        foreach ($application->getModel('Filter', 'View')->bundleName_is($bundle->name)->fetch()->with('Field', 'FieldConfig') as $filter) {
            if (!$element = $filter->toDisplayElementArray()) {
                // Field for the filter no longer exists, so manually remove filter
                $filter->markRemoved()->commit();
                continue;
            }
            
            $elements[$filter->name] = $element;
            // Need ID to use the current filter
            $elements[$filter->name]['data']['settings']['filter_id'] = $filter->id;
        }
        return $elements;
    }
    
    protected function _getField(Application $application, Model\Filter $filter, $value = null, Entity\Type\Query $query = null, array $current = null)
    {
        if (isset($current)) {
            if (!isset($query)) return $current;
            
            if (!$ifilter = $application->Field_Filters_impl($filter->type, true)) return;
            
            if (!$ifilter->fieldFilterInfo('facetable')) return $current;
        } else {
            if (!$ifilter = $application->Field_Filters_impl($filter->type, true)) return;
        }      
        $settings = $filter->data['settings'] + (array)$ifilter->fieldFilterInfo('default_settings');
        if ((!$field = $filter->getField())
            || (!$filter_form = $ifilter->fieldFilterForm($field, $filter->name, $settings, $value, $query, $current, array($filter->name)))
        ) {
            return;
        }
        $filter_form['#data']['view-filter-name'] = $filter->name;
        if (isset($filter_form['#entity_filter_form_type'])) {
            $filter_form['#data']['view-filter-form-type'] = $filter_form['#entity_filter_form_type'];
        }
        $class = 'drts-view-filter-form-field drts-view-filter-form-field-type-' . str_replace('_', '-', $filter->type);
        if (isset($filter_form['#class'])) {
            $filter_form['#class'] .= ' ' . $class;
        } else {
            $filter_form['#class'] = $class;
        }
        
        return array(
            '#tree' => true,
            '#weight' => isset($filter->data['weight']) ? $filter->data['weight'] : 0,
        ) + $filter_form;
    }
}