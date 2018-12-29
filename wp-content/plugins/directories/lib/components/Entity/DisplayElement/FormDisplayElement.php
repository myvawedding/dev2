<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Exception;

class FormDisplayElement extends Display\Element\AbstractElement
{
    protected $_fieldType;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_fieldType = substr($this->_name, 12); // remove entity_form_ part
    }

    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        $field_type = $this->_application->Field_Type($this->_fieldType);
        return array(
            'type' => 'field',
            'label' => $label = $field_type->fieldTypeInfo('label'),
            'icon' => $field_type->fieldTypeInfo('icon'),
            'description' => sprintf(__('Adds a %s type custom field', 'directories'), $label),
            'default_settings' => [],
            'alignable' => true,
            'positionable' => true,
            'creatable' => $creatable = false !== $field_type->fieldTypeInfo('creatable'),
            'listable' => $creatable,
            'visibility' => $field_type->fieldTypeInfo('visibility'),
            'headingable' => false,
        );
    }

    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'form';
    }

    protected function _getField(Entity\Model\Bundle $bundle, array $settings)
    {
        if (!empty($settings['field_name'])
            && ($field = $this->_application->Entity_Field($bundle, $settings['field_name']))
            && $field->getFieldType() === $this->_fieldType
        ) {
            return $field;
        }
    }

    protected function _getFieldWidgets(Entity\Model\Field $field = null)
    {
        $field_types = $this->_application->Field_Types();
        $widgets = (array)@$field_types[$this->_fieldType]['widgets'];
        foreach (array_keys($widgets) as $widget) {
            if ((!$field_widget = $this->_application->Field_Widgets_impl($widget, true))
                || !$field_widget->fieldWidgetSupports($field ? $field : $this->_fieldType)
            ) {
                unset($widgets[$widget]);
            }
        }
        return $widgets;
    }

    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        if (!$field_type = $this->_application->Field_Type($this->_fieldType, true)) return;

        $field = $this->_getField($bundle, $settings);
        if (!$widgets = $this->_getFieldWidgets($field)) return;

        if ($tab === 'conditions') {
            $conditionable_fields = $this->_application->Entity_Field($bundle);
            if ($field) {
                unset($conditionable_fields[$field->getFieldName()]); // remove self
            }
            foreach (array_keys($conditionable_fields) as $field_name) {
                $_field = $conditionable_fields[$field_name];
                if ((!$field_type = $this->_application->Field_Type($_field->getFieldType(), true))
                    || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IConditionable
                ) {
                    unset($conditionable_fields[$field_name]);
                }
            }
            if (empty($conditionable_fields)) return;

            $condition_settings = $field ? $field->getFieldConditions() : [];
            $form = [
                'add' => [
                    '#type' => 'checkbox',
                    '#title' => __('Add conditional rules', 'directories'),
                    '#default_value' => !empty($condition_settings['add']),
                    '#horizontal' => true,
                ],
                'action' => [
                    '#horizontal' => true,
                    '#row' => true,
                    '#class' => DRTS_BS_PREFIX . 'mb-0',
                    '#title' => ' ',
                    'name' => [
                        '#type' => 'select',
                        '#options' => [
                            'show' => __('Show this field', 'directories'),
                            'hide' => __('Hide this field', 'directories'),
                        ],
                        '#default_value' => isset($condition_settings['action']['name']) ? $condition_settings['action']['name'] : 'show',
                        '#weight' => 1,
                        '#col' => 6,
                    ],
                    'match' => [
                        '#type' => 'select',
                        '#options' => [
                            'all' => __('if all of the following match', 'directories'),
                            'any' => __('if any of the following matches', 'directories'),
                        ],
                        '#default_value' => isset($condition_settings['action']['match']) ? $condition_settings['action']['match'] : 'all',
                        '#weight' => 2,
                        '#col' => 6,
                    ],
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['add']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'rules' => [
                    '#title' => ' ',
                    '#horizontal' => true,
                    '#class' => DRTS_BS_PREFIX . 'mb-0',
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['add']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ];
            if (isset($submitValues['rules'])) {
                // coming from form submission
                // need to check request values since fields may have been added/removed
                $rules = empty($submitValues['rules']) ? [null] : $submitValues['rules'];
            } else {
                if (!empty($condition_settings['rules'])) {
                    $rules = $condition_settings['rules'];
                } else {
                    $rules = [];
                }
                $rules[] = null; // for adding a new rule
            }
            foreach ($rules as $i => $rule) {
                $form['rules'][$i] = array(
                    '#type' => 'field_condition',
                    '#fields' => $conditionable_fields,
                    '#default_value' => $rule,
                );
            }
            $form['rules']['_add'] = [
                '#type' => 'addmore',
                '#next_index' => ++$i,
            ];
            return $form;
        }

        $field_type_info = $field_type->fieldTypeInfo();

        $form = [
            '#bundle' => $bundle,
        ];
        if (!$field
            || $field->isCustomField()
        ) {
            $form['#tabs'] = [
                'conditions' => __('Conditions', 'directories'),
            ];
        }

        $form['label'] = array(
            '#title' => __('Label', 'directories'),
            '#type' => 'textfield',
            '#max_length' => 255,
            '#required' => true,
            '#weight' => 1,
            '#horizontal' => true,
            '#default_value' => $field ? $field->getFieldLabel() : null,
        );

        $field_prefix = $this->_application->Form_FieldName($parents);
        if ($field) {
            $form['_name'] = array(
                '#type' => 'textfield',
                '#title' => __('Field name', 'directories'),
                '#value' => $field->getFieldName(),
                '#horizontal' => true,
                '#disabled' => true,
                '#weight' => 2,
            );
            $form['field_name'] = array(
                '#type' => 'hidden',
                '#value' => $field->getFieldName(),
            );
            $form['#field_name'] = $field->getFieldName(); // let other components access this field on build form filter event
        } else {
            $form['name'] = array(
                '#type' => 'textfield',
                '#title' => __('Field name', 'directories'),
                '#description' => __('Enter a machine readable name which may not be changed later. Only lowercase alphanumeric characters and underscores are allowed.', 'directories'),
                '#max_length' => 50,
                '#required' => true,
                '#weight' => 2,
                '#regex' => '/^[a-z0-9_]+$/',
                '#field_prefix' => 'field_',
                '#horizontal' => true,
                '#states' => array(
                    'slugify' => array(
                        sprintf('input[name="%s[label]"]', $field_prefix) => array('type' => 'filled', 'value' => true),
                    ),
                ),
            );

            $existing_fields = [];
            foreach ($this->_application->getModel('FieldConfig', 'Entity')->type_is($this->_fieldType)->fetch() as $field_config) {
                if (strpos($field_config->name, 'field_') === 0 // custom field only
                    && !$this->_application->Entity_Field($bundle, $field_config->name) // make sure the field has not yet been added to the bundle
                ) {
                    $existing_fields[$field_config->name] = __('Use existing field', 'directories') . ' - ' . $field_config->name;
                }
            }
            if (!empty($existing_fields)) {
                $form['existing_field_name'] = [
                    '#title' => __('New or existing field', 'directories'),
                    '#type' => 'select',
                    '#options' => ['' => __('Create new field', 'directories')] + $existing_fields,
                    '#horizontal' => true,
                    '#required' => true,
                ];
                $form['name']['#states']['visible'] = [
                    sprintf('[name="%s[existing_field_name]"]', $field_prefix) => array('value' => ''),
                ];
            }
        }

        $form['description'] = array(
            '#type' => 'textarea',
            '#title' => __('Description', 'directories'),
            '#description' => __('Enter a short description of the field displayed to the user.', 'directories'),
            '#rows' => 3,
            '#default_value' => $field ? $field->getFieldDescription() : null,
            '#weight' => 6,
            '#horizontal' => true,
        );

        if ((empty($bundle->info['is_taxonomy']) && !empty($field_type_info['disablable']))
            || ($field && $field->isCustomField())
        ) {
            $form['disabled'] = array(
                '#type' => 'checkbox',
                '#title' => __('Disabled', 'directories'),
                '#default_value' => null,
                '#weight' => 7,
                '#horizontal' => true,
                '#default_value' => $field ? (bool)$field->getFieldData('disabled') : null,
            );
        }

        if (!isset($field_type_info['requirable']) || false !== $field_type_info['requirable']) {
            $form['required'] = array(
                '#type' => 'checkbox',
                '#title' => __('Required', 'directories'),
                '#default_value' => null,
                '#weight' => 8,
                '#horizontal' => true,
                '#default_value' => $field ? $field->isFieldRequired() : null,
            );
        }

        // Add field type settings form
        $field_settings = $field ? $field->getFieldSettings() : [];
        if (!empty($field_type_info['default_settings'])) {
            $field_settings += $field_type_info['default_settings'];
        }
        $field_settings_parents = $parents;
        $field_settings_parents[] = 'settings';
        if ($settings_form = (array)@$field_type->fieldTypeSettingsForm($field ? $field : $this->_fieldType, $bundle, $field_settings, $field_settings_parents)) {
            if (isset($settings_form['#header'])) {
                $form['#header'] = array_merge($form['#header'], $settings_form['#header']);
            }
            $form['settings'] = array(
                '#type' => 'fieldset',
                '#tree' => true,
                '#tree_allow_override' => false,
                '#weight' => 40,
            );
            if ($field) {
                $form['settings'] += array(
                    '#description' => sprintf(
                        $this->_application->H(__('The following settings are applied automatically to all instances of the %s field.', 'directories')),
                        '<em>' . $field->getFieldName() . '</em>'
                    ),
                    '#description_no_escape' => true,
                    '#group' => true,
                    '#class' => DRTS_BS_PREFIX . 'bg-warning ' . DRTS_BS_PREFIX . 'p-2',
                );
            }
            foreach (array_keys($settings_form) as $key) {
                if (strpos($key, '#') === false) {
                    $settings_form[$key]['#horizontal'] = true;
                }
                $form['settings'][$key] = $settings_form[$key];
            }

            // For existing fields, field type settings are shown only when editing
            if (!empty($existing_fields)) {
                $form['settings']['#states']['visible'][sprintf('[name="%s[existing_field_name]"]', $field_prefix)] = [
                    'value' => '',
                ];
            }
        }

        // Add field widget settings form for each widget available
        if ($field
            && $field->getFieldWidget()
        ) {
            $current_widget = $field->getFieldWidget();
            $current_widget_settings = $field->getFieldWidgetSettings();
        } else {
            $field_types = $this->_application->Field_Types();
            $current_widget = isset($field_types[$this->_fieldType]['default_widget']) ? $field_types[$this->_fieldType]['default_widget'] : $this->_fieldType;
            $current_widget_settings = [];
        }
        if (!isset($widgets[$current_widget])) {
            $current_widget = null;
        }
        if (count($widgets) === 1) {
            $form['widget'] = array(
                '#type' => 'hidden',
                '#value' => current(array_keys($widgets)),
            );
        } else {
            $form['widget'] = array(
                '#type' => 'select',
                '#title' => __('Form field type', 'directories'),
                '#options' => $widgets,
                '#weight' => 50,
                '#default_value' => $current_widget,
                '#horizontal' => true,
            );
        }
        $form['widget_settings'] = array(
            '#tree' => true,
            '#weight' => 51,
        );
        foreach (array_keys($widgets) as $widget) {
            $field_widget = $this->_application->Field_Widgets_impl($widget);
            $field_widget_info = $field_widget->fieldWidgetInfo();
            $is_current_widget = $current_widget && $current_widget === $widget;
            $widget_settings = $is_current_widget ? $current_widget_settings : [];
            if (!empty($field_widget_info['default_settings'])) {
                $widget_settings += $field_widget_info['default_settings'];
            }
            $widget_settings_parents = $parents;
            $widget_settings_parents[] = 'widget_settings';
            $widget_settings_parents[] = $widget;
            $widget_settings_form = $field_widget->fieldWidgetSettingsForm($field ? $field : $this->_fieldType, $bundle, $widget_settings, $widget_settings_parents, $parents);
            if ($widget_settings_form) {
                $form['widget_settings'][$widget] = $widget_settings_form;
            }
            // Add an option to make this field repeatable if the widget supports the feature
            if (empty($field_widget_info['accept_multiple'])) {
                if (!empty($field_widget_info['disable_edit_max_num_items']) || empty($field_widget_info['repeatable'])) {
                    $disable_edit_max_num_items = true;
                } else {
                    $disable_edit_max_num_items = false;
                }
                $default_max_num_items = 1;
            } else {
                $disable_edit_max_num_items = !empty($field_widget_info['disable_edit_max_num_items']);
                $default_max_num_items = 0;
            }
            if (!$disable_edit_max_num_items) {
                $form['widget_settings'][$widget]['max_num_items'] = array(
                    '#type' => 'slider',
                    '#min_value' => 0,
                    '#min_text' => __('Unlimited', 'directories'),
                    '#max_value' => 20,
                    '#title' => __('Max number of values', 'directories'),
                    '#description' => __('Max number of values users can enter for this field.', 'directories'),
                    '#default_value' => $is_current_widget && $field ? $field->getFieldMaxNumItems() : $default_max_num_items,
                    '#weight' => 60,
                    '#horizontal' => true,
                );
            } else {
                $form['widget_settings'][$widget]['max_num_items'] = array(
                    '#type' => 'hidden',
                    '#value' => isset($field_widget_info['max_num_items']) ? $field_widget_info['max_num_items'] : $default_max_num_items,
                );
            }

            foreach (array_keys($form['widget_settings'][$widget]) as $key) {
                if (false === strpos($key, '#')) {
                    $form['widget_settings'][$widget][$key]['#horizontal'] = true;
                }
            }
            $form['widget_settings'][$widget]['#states']['visible'] = array(
                sprintf('[name="%s[widget]"]', $field_prefix) => array('value' => $widget),
            );
        }

        // Add personal data settings if the field implements IPersonalData
        if ($field_type instanceof \SabaiApps\Directories\Component\Field\Type\IPersonalData) {
            $form['_is_personal_data'] = [
                '#title' => __('Personal data', 'directories'),
                '#type' => 'checkbox',
                '#switch' => false,
                '#default_value' => $field && (bool)$field->getFieldData('_is_personal_data'),
                '#on_label' => __('This field contains personal data', 'directories'),
                '#horizontal' => true,
                '#weight' => 100,
            ];
            $form['_personal_data_identifier'] = [
                '#type' => 'select',
                '#description_top' => true,
                '#description' => __('Personal data belongs to the person identified by:', 'directories'),
                '#options' => $this->_application->Entity_PersonalData_identifierFieldOptions($bundle),
                '#default_value' => $field ? $field->getFieldData('_personal_data_identifier') : null,
                '#horizontal' => true,
                '#weight' => 101,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[_is_personal_data][]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ];
        }

        return $form;
    }

    protected function _getMaxNumItemsOptions($widget)
    {
        if ($max_num_items_options = $widget->fieldWidgetInfo('max_num_items_options')) {
            return array_combine($max_num_items_options, $max_num_items_options);
        }
        return array(__('Unlimited', 'directories')) + array_combine(range(1, 10), range(1, 10));
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $form_settings = $var->settings;
        if ($form_settings['#wrap']) {
            foreach ($form_settings['#wrap'] as $wrap) {
                $form_settings = $form_settings[$wrap];
            }
        }
        if (!empty($form_settings[$element['settings']['field_name']]['#admin_only'])
            && $this->_application->getPlatform()->isAdmin()
        ) return;

        return $var->render()->getHtml($element['settings']['field_name'], $var->settings['#wrap']);
    }

    public function displayElementAdminTitle(Entity\Model\Bundle $bundle, array $element)
    {
        return $this->_application->H($element['settings']['label']);
    }

    public function displayElementIsDimmed(Entity\Model\Bundle $bundle, array $settings)
    {
        return ($field = $this->_getField($bundle, $settings)) && $field->getFieldData('disabled');
    }

    public function displayElementOnRemoved(Entity\Model\Bundle $bundle, array $settings)
    {
        if (($field = $this->_getField($bundle, $settings))
            && $field->isCustomField()
        ) {
            $field_config = $field->FieldConfig;
            $field->markRemoved()->commit();
            if (!count($field_config->Fields)) {
                $field_config->markRemoved()->commit();
                $this->_application->getComponent('Entity')->deleteFieldStorage(array($field_config));
            }
        }
    }

    public function displayElementOnPositioned(Entity\Model\Bundle $bundle, array $settings, $weight)
    {
        if ($field = $this->_getField($bundle, $settings)) {
            $field->setFieldWeight($weight)->commit();
        }
    }

    public function displayElementOnCreate(Entity\Model\Bundle $bundle, array &$data, $weight)
    {
        $settings = $data['settings'];
        if (!$field = $this->_getField($bundle, $settings)) {
            // Creating new field, make sure the field name is unique
            if (isset($settings['field_name'])
                && strlen($settings['field_name'] = trim(trim($settings['field_name']), '_'))
            ) {
                $field_name = $settings['field_name'];
            } elseif (isset($settings['existing_field_name'])
                && strlen($settings['existing_field_name'] = trim($settings['existing_field_name']))
            ) {
                $field_name = $settings['existing_field_name'];
                $is_existing_field = true;
            }  elseif (isset($settings['name'])
                && strlen($settings['name'] = trim(trim($settings['name']), '_'))
            ) {
                $field_name = 'field_' . $settings['name'];
            } else {
                throw new Exception\RuntimeException('Invalid field name');
            }

            // Do not create if non-custom field
            if (strpos($field_name, 'field_') !== 0) {
                throw new Exception\RuntimeException('System field can not be created. Field name: ' . $field_name);
            }

            if (empty($is_existing_field)) {
                if ($this->_application->getModel('FieldConfig', 'Entity')->name_is($field_name)->count() > 0) {
                    throw new Exception\RuntimeException(__('The name is already in use by another field.', 'directories'));
                }
            } else {
                if ($this->_application->Entity_Field($bundle, $field_name)) {
                    throw new Exception\RuntimeException(sprintf(__('The field is already added to %s.', 'directories'), $bundle->getLabel()));
                }
            }
        }

        $widget = $settings['widget'];
        $widget_settings = isset($settings['widget_settings'][$widget]) ? $settings['widget_settings'][$widget] : $settings['widget_settings'];
        if (isset($settings['max_num_items'])) {
            $max_num_items = $settings['max_num_items']; // coming from import
        } else {
            $max_num_items = $widget_settings['max_num_items'];
            unset($widget_settings['max_num_items']);
        }
        $conditions = [];
        if (!empty($settings['conditions']['rules'])
            && ($settings['conditions']['rules'] = array_filter($settings['conditions']['rules']))
        ) {
            $conditions = $settings['conditions'];
        }
        $field_data = array(
            'type' => $this->_fieldType,
            'settings' => isset($settings['settings']) ? $settings['settings'] : [],
            'label' => $settings['label'],
            'description' => $settings['description'],
            'disabled' => !empty($settings['disabled']),
            'required' => !empty($settings['required']),
            'widget' => $widget,
            'widget_settings' => $widget_settings,
            'max_num_items' => $max_num_items,
            'weight' => $weight,
            'conditions' => $conditions,
        );
        if (isset($settings['default_value'])) {
            $field_data['default_value'] = is_array($settings['default_value']) && isset($settings['default_value'][0])
                ? $settings['default_value']
                : array($settings['default_value']);
        }
        // Is it personal data?
        if (isset($settings['_is_personal_data'])) {
            $field_data['data']['_is_personal_data'] = !empty($settings['_is_personal_data']);
            $field_data['data']['_personal_data_identifier'] = $settings['_personal_data_identifier'];
        }

        // Allow components to modify field data
        $field_data = $this->_application->Filter('field_field_data', $field_data, array($bundle, &$data));

        if ($field) {
            if ($field->isPropertyField()) {
                $this->_application->getComponent('Entity')->createEntityPropertyField(
                    $bundle,
                    $field->FieldConfig,
                    $field_data,
                    true // commit
                );
            } else {
                $this->_application->getComponent('Entity')->createEntityField(
                    $bundle,
                    $field->FieldConfig,
                    $field_data,
                    true // overwrite
                );
            }
            $field_name = $field->getFieldName();

            if (($field_type = $this->_application->Field_Type($field->getFieldType(), true))
                && $field_type->fieldTypeInfo('entity_cache_clear')
            ) {
                $this->_application->Entity_FieldCache_clean($bundle->entitytype_name);
            }
        } else {
            $this->_application->getComponent('Entity')->createEntityField(
                $bundle,
                $field_name,
                $field_data
            );
        }

        $data['settings'] = array('field_name' => $field_name, 'label' => $settings['label']);
    }

    public function displayElementOnUpdate(Entity\Model\Bundle $bundle, array &$data, $weight)
    {
        $this->displayElementOnCreate($bundle, $data, $weight);
    }

    public function displayElementOnExport(Entity\Model\Bundle $bundle, array &$data)
    {
        $settings = $data['settings'];
        if (!$field = $this->_getField($bundle, $settings)) {
            throw new Exception\RuntimeException('Failed exporting field');
        }

        $data['settings'] = $field->getDisplayElementData();
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        if (!$field = $this->_getField($bundle, $settings)) return;

        $ret = [
            'type' => [
                'label' => __('Field type', 'directories'),
                'value' => $field->getFieldType(),
            ],
            'name' => [
                'label' => __('Field name', 'directories'),
                'value' => $field->getFieldName(),
            ],
        ];
        if ($field->getFieldDescription()) {
            $ret['description'] = [
                'label' => __('Description', 'directories'),
                'value' => $field->getFieldDescription(),
            ];
        }
        if ($field->getFieldData('disabled')) {
            $ret['disabled'] = [
                'label' => __('Disabled', 'directories'),
                'value' => true,
                'is_bool' => true,
            ];
        }
        if ($field->getFieldData('required')) {
            $ret['required'] = [
                'label' => __('Required', 'directories'),
                'value' => true,
                'is_bool' => true,
            ];
        }
        if (($widget = $field->getFieldWidget())
            && ($widgets = $this->_getFieldWidgets($field))
            && isset($widgets[$widget])
        ) {
            $ret['widget'] = [
                'label' => __('Form field type', 'directories'),
                'value' => $widgets[$widget],
            ];
        }
        $ret['max_num_items'] = [
            'label' => __('Max number of values', 'directories'),
            'value' => (0 === $max = $field->getFieldMaxNumItems()) ? __('Unlimited', 'directories') : $max,
        ];

        return ['settings' => ['value' => $ret]];
    }
}
