<?php
namespace SabaiApps\Directories\Component\View\DisplayElement;

use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class FilterDisplayElement extends Display\Element\AbstractElement
{
    protected function _getField($bundleName)
    {
        $field_name = substr($this->_name, 12); // remove view_filter_ part
        if (!$field = $this->_application->Entity_Field($bundleName, $field_name)) {
            throw new Exception\RuntimeException(sprintf('Invalid field %s for bundle %s', $field_name, $bundleName));
        }
        return $field;
    }

    protected function _getFilter($bundleName, $filterName, Field\IField $field = null)
    {
        if (!isset($field)) {
            $field = $this->_getField($bundleName);
        }
        if (!$filter = $this->_application->getModel('Filter', 'View')->fieldId_is($field->getFieldId())->name_is($filterName)->fetchOne()) {
            throw new Exception\RuntimeException(sprintf('Invalid filter %s for bundle %s', $filterName, $bundleName));
        }
        return $filter;
    }

    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        $field = $this->_getField($bundle->name);
        return array(
            'type' => 'field',
            'label' => $field->getFieldLabel(),
            'description' => sprintf(__('Field name: %s', 'directories'), $field->getFieldName()),
            'default_settings' => [],
            'alignable' => true,
            'positionable' => true,
            'icon' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('icon'),
            'headingable' => false,
        );
    }

    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->type !== 'filters') return false;

        try {
            $field = $this->_getField($bundle->name);
        } catch (\Exception $e) {
            $this->_application->LogError($e);
            return false;
        }

        if (($bundle_name = $field->getFieldData('_bundle_name'))
            && (!$this->_application->Entity_Bundle($bundle_name))
        ) {
            return false;
        }
        return true;
    }

    protected function _getFieldFilters(Field\IField $field)
    {
        $filters = [];
        $field_types = $this->_application->Field_Types();
        if (!empty($field_types[$field->getFieldType()]['filters'])) {
            $filters = $field_types[$field->getFieldType()]['filters'];
            foreach (array_keys($filters) as $filter_type) {
                if ((!$field_filter = $this->_application->Field_Filters_impl($filter_type, true))
                    || !$field_filter->fieldFilterSupports($field)
                ) {
                    unset($filters[$filter_type]);
                }
            }
        }
        return $filters;
    }

    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $field = $this->_getField($bundle->name);
        $filters = $this->_getFieldFilters($field);

        // Make sure the current filter is valid if any
        $filter = null;
        if (isset($settings['filter_name'])) {
            $filter = $this->_getFilter($bundle->name, $settings['filter_name'], $field);
            if (!isset($filters[$filter->type])) {
                // This filter is invalid or has become invalid
                $filter->markRemoved()->commit();
                throw new Exception\RuntimeException('Invalid filter type ' . $filter->type . ' for field type ' . $field->getFieldType());
            }
        }

        if (empty($filters)) {
            throw new Exception\RuntimeException('No filter is avaialbe for field type ' . $field->getFieldType());
        }

        $form = $this->_application->Display_ElementLabelSettingsForm($settings, $parents) + array(
            'label_as_heading' => array(
                '#title' => __('Show label as heading', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['label_as_heading']),
                '#horizontal' => true,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[label]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'none'),
                    ),
                ),
                '#weight' => -4,
            ),
            'filter' => array(
                '#type' => 'select',
                '#title' => __('Field filter', 'directories'),
                '#options' => $filters,
                '#weight' => -1,
                '#default_value' => isset($filter) ? $filter->type : null,
                '#horizontal' => true,
            ),
            'filter_settings' => array(
                '#tree' => true,
            ),
        );
        if (count($filters) === 1) {
            if (!isset($form['filter']['#default_value'])) {
                $filter_names = array_keys($filters);
                $form['filter']['#default_value'] = $filter_names[0];
            }
            $form['filter']['#type'] = 'hidden';
        }
        foreach (array_keys($filters) as $filter_type) {
            $field_filter = $this->_application->Field_Filters_impl($filter_type);
            $filter_settings = isset($filter) && $filter->type === $filter_type ? $filter->data['settings'] : [];
            $filter_settings += (array)$field_filter->fieldFilterInfo('default_settings');
            $filter_settings_parents = $parents;
            $filter_settings_parents[] = 'filter_settings';
            $filter_settings_parents[] = $filter_type;
            $filter_settings_form = $field_filter->fieldFilterSettingsForm($this->_getField($bundle->name), $filter_settings, $filter_settings_parents);
            if ($filter_settings_form) {
                $form['filter_settings'][$filter_type] = $filter_settings_form;
                foreach (array_keys($form['filter_settings'][$filter_type]) as $key) {
                    if (false === strpos($key, '#')) {
                        $form['filter_settings'][$filter_type][$key]['#horizontal'] = true;
                    }
                }
                $form['filter_settings'][$filter_type]['#states']['visible'] = array(
                    sprintf('[name="%s[filter]"]', $this->_application->Form_FieldName($parents)) => array('value' => $filter_type),
                );
            }
        }

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => __('Filter name', 'directories'),
            '#description' => __('Enter a machine readable name which may not be changed later. Only lowercase alphanumeric characters and underscores are allowed.', 'directories'),
            '#max_length' => 255,
            '#required' => true,
            '#weight' => -99,
            '#regex' => '/^[a-z0-9_]+$/',
            '#field_prefix' => 'filter_',
            '#horizontal' => true,
            '#slugify' => true,
            '#default_value' => isset($filter)
                ? (strpos($filter->name, 'filter_') === 0 ? substr($filter->name, 7) : $filter->name)
                : $field->getFieldName(),
        );
        if (!isset($filter)) {
            $form['name']['#states']['slugify'] = array(
                sprintf('input[name="%s[label_custom]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'filled', 'value' => true),
            );
        } else {
            $form['filter_id'] = array(
                '#type' => 'hidden',
                '#value' => $filter->id,
            );
        }

        return $form;
    }

    public function isCustomLabelRequired($form, $parents)
    {
        $form_values = $form->getValue($parents);
        return $form_values['label'] === 'custom';
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $settings = $element['settings'];
        if (!$html = $var->render()->getHtml($settings['filter_name'])) return;

        $html = '<div class="drts-view-filter-field">' . $html . '</div>';
        $label_type = $settings['label'];
        $label = $this->_application->Display_ElementLabelSettingsForm_label(
            $settings,
            $this->displayElementStringId('label', $element['element_id']),
            $this->_getField($bundle->name)->getFieldLabel(true)
        );
        if (!strlen($label)) return $html;

        if (empty($settings['label_as_heading'])) {
            $heading_class = '';
        } else {
            $heading_class = ' drts-display-element-header';
            $label = '<span>' . $label . '</span>';
        }
        return '<div class="drts-view-filter-field-label drts-view-filter-field-label-type-' . $label_type . $heading_class . '">' . $label . '</div>'
            . $html;
    }

    public function displayElementAdminTitle(Entity\Model\Bundle $bundle, array $element)
    {
        return $this->_application->Display_ElementLabelSettingsForm_label($element['settings'], null, $this->_getField($bundle->name)->getFieldLabel());
    }

    public function displayElementOnRemoved(Entity\Model\Bundle $bundle, array $settings)
    {
        if (0 !== strpos($settings['filter_name'], 'filter_')) return; // default filters may not be removed

        $this->_getFilter($bundle->name, $settings['filter_name'])->markRemoved()->commit();
    }

    public function displayElementOnPositioned(Entity\Model\Bundle $bundle, array $settings, $weight)
    {
        $filter = $this->_getFilter($bundle->name, $settings['filter_name']);
        $filter->data = array('weight' => $weight) + $filter->data;
        $filter->commit();
    }

    public function displayElementOnCreate(Entity\Model\Bundle $bundle, array &$data, $weight)
    {
        $settings = $data['settings'];
        $filter = null;
        if (!empty($settings['filter_id'])
            && (!$filter = $this->_application->getModel('Filter', 'View')->fetchById($settings['filter_id']))
        ) {
            throw new Exception\RuntimeException('Invalid filter id'); // this should not happen
        }

        // Make sure the filter name is unique
        $filter_name = isset($settings['filter_name']) ? $settings['filter_name'] : 'filter_' . $settings['name'];
        $name_query = $this->_application->getModel('Filter', 'View')->bundleName_is($bundle->name)->name_is($filter_name);
        if ($filter) {
            $name_query->id_isNot($filter->id);
        }
        if ($name_query->count() > 0) {
            throw new Exception\RuntimeException(__('The name is already in use by another field.', 'directories'));
        }

        // Make sure the field is filterable
        $field = $this->_getField($bundle->name);
        $field_types = $this->_application->Field_FilterableFieldTypes($bundle);
        if (!isset($field_types[$field->getFieldType()])
            || !isset($field_types[$field->getFieldType()]['filters'][$settings['filter']])
        ) {
            throw new Exception\RuntimeException(__('The field is not filterable.', 'directories'));
        }

        // Create or update filter
        if (!$filter) {
            $filter = $this->_application->getModel(null, 'View')->create('Filter')->markNew();
            $filter->field_id = $field->id;
            $filter->bundle_name = $field->bundle_name;
        }
        $filter_settings = isset($settings['filter_settings'][$settings['filter']]) ? $settings['filter_settings'][$settings['filter']] : [];
        unset($data['settings']['filter_settings']);
        $filter->type = $settings['filter'];
        $filter->name = $filter_name;
        $filter->data = array(
            'settings' => $filter_settings,
        ) + (array)$filter->data;
        $filter->commit();

        $data['settings'] += array(
            'field_name' => $field->getFieldName(),
            'filter_name' => $filter->name,
        );
    }

    public function displayElementOnUpdate(Entity\Model\Bundle $bundle, array &$data, $weight)
    {
        $this->displayElementOnCreate($bundle, $data, $weight);
    }

    public function displayElementOnSaved(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        if (isset($element->data['settings']['label'])
            && in_array($element->data['settings']['label'], array('custom', 'custom_icon'))
        ) {
            $this->_registerString($element->data['settings']['label_custom'], 'label', $element->id);
        } else {
            $this->_unregisterString('label', $element->id);
        }
    }

    public function displayElementOnExport(Entity\Model\Bundle $bundle, array &$data)
    {
        $settings = $data['settings'];

        if (!isset($settings['filter_name'])) {
            throw new Exception\RuntimeException('Failed exporting filter');
        }
        $filter = $this->_getFilter($bundle->name, $settings['filter_name']);
        $data['settings']['filter_settings'][$settings['filter']] = $filter->data['settings'];
        // Unset filter ID so that the filter is created on import
        unset($data['settings']['filter_id']);
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'filter_name' => [
                'label' => __('Filter name', 'directories'),
                'value' => $settings['filter_name'],
            ],
        ];
        try {
            if (($field = $this->_getField($bundle))
                && ($filters = $this->_getFieldFilters($field))
                && ($filter = $this->_getFilter($bundle->name, $settings['filter_name'], $field))
                && isset($filters[$filter->type])
            ) {
                $ret['filter'] = [
                    'label' => __('Field filter', 'directories'),
                    'value' => $filters[$filter->type],
                ];
            }
        } catch (Exception\IException $e) {
            $this->_application->LogError($e);
        }
        return ['settings' => ['value' => $ret]];
    }
}
