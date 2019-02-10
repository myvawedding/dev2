<?php
namespace SabaiApps\Directories\Component\Location\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Map;
use SabaiApps\Directories\Request;

class AddressFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Map', 'directories-pro'),
            'field_types' => array($this->_name),
            'default_settings' => array(
                'latlng_required_if_address' => true,
                'map_type' => 'roadmap',
                'map_height' => 300,
                'center_latitude' => 40.69847,
                'center_longitude' => -73.95144,
                'zoom' => 10,
                'input_fields' => array(
                    'options' => array(
                        'street' => __('Address Line 1', 'directories-pro'),
                        'street2' => __('Address Line 2', 'directories-pro'),
                        'zip' => __('Postal / Zip Code', 'directories-pro'),
                        'city' => __('City', 'directories-pro'),
                        'province' => __('State / Province / Region', 'directories-pro'),
                    ),
                    'default' => array('street', 'zip'),
                ),
                'input_country' => null,
                'hide_timezone_if_no_map' => false,
                'allow_empty' => false,
            ),
            'repeatable' => true,
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $ret = [];
        if (is_object($fieldType)
            && !$fieldType->isCustomField()
            && ($location_bundle = $this->_getLocationBundle($fieldType))
            && $this->_application->Location_Hierarchy($location_bundle)
            && $this->_hasTopLevelLocations($location_bundle)
        ) {
            $default_settings = $this->fieldWidgetInfo('default_settings');
            $settings['input_fields']['options'] += $default_settings['input_fields']['options'];
            $options_disabled = [];
            $description = null;
            // Define country if hierarchy does not contain countries
            if ($hierarchy = $this->_application->Location_Hierarchy($location_bundle)) {
                if (!isset($hierarchy['country'])) {
                    $ret['input_country'] = array(
                        '#type' => 'select',
                        '#title' => __('Country', 'directories-pro'),
                        '#default_value' => $settings['input_country'],
                        '#options' => array_combine($countries = $this->_application->System_Countries(), $countries),
                    );
                }
                foreach (array_keys($hierarchy) as $location_level_key) {
                    if (!isset($settings['input_fields']['options'][$location_level_key])) continue;

                    $options_disabled[] = $location_level_key;
                    if (isset($settings['input_fields']['default'][$location_level_key])) {
                        unset($settings['input_fields']['default'][$location_level_key]);
                    }
                }
                if (!empty($options_disabled)) {
                    $description = sprintf(
                        $this->_application->H(__('%s: Already in use in %s.')),
                        '<em>' . implode('</em>, <em>', $options_disabled) . '</em>',
                        $this->_application->H(__('Location Hierarchy Settings', 'directories-pro'))
                    );
                }
            }
            $ret['input_fields'] = array(
                '#multiple' => true,
                '#title' => __('Address input fields', 'directories-pro'),
                '#type' => 'options',
                '#default_value' => $settings['input_fields'],
                '#disable_add' => true,
                '#disable_icon' => true,
                '#options_value_disabled' => true,
                '#options_disabled' => $options_disabled,
                '#description' => $description,
                '#description_no_escape' => true,
            );
        }

        $ret += [
            'map_height' => array(
                '#type' => 'textfield',
                '#size' => 4,
                '#maxlength' => 3,
                '#field_suffix' => 'px',
                '#title' => __('Map height', 'directories-pro'),
                '#description' => __('Enter the height of map in pixels.', 'directories-pro'),
                '#default_value' => $settings['map_height'],
                '#numeric' => true,
            ),
            'center_latitude' => array(
                '#type' => 'textfield',
                '#maxlength' => 20,
                '#title' => __('Default latitude', 'directories-pro'),
                '#description' => __('Enter the latitude of the default map location in decimals.', 'directories-pro'),
                '#default_value' => $settings['center_latitude'],
                '#regex' => Map\MapComponent::LAT_REGEX,
                '#numeric' => true,
            ),
            'center_longitude' => array(
                '#type' => 'textfield',
                '#maxlength' => 20,
                '#title' => __('Default longitude', 'directories-pro'),
                '#description' => __('Enter the longitude of the default map location in decimals.', 'directories-pro'),
                '#default_value' => $settings['center_longitude'],
                '#regex' => Map\MapComponent::LNG_REGEX,
                '#numeric' => true,
            ),
            'zoom' => array(
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => 19,
                '#title' => __('Default zoom level', 'directories-pro'),
                '#default_value' => $settings['zoom'],
                '#integer' => true,
            ),
            'allow_empty' => [
                '#type' => 'checkbox',
                '#title' => __('Allow empty location', 'directories-pro'),
                '#default_value' => !empty($settings['allow_empty']),
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[required]"]', $this->_application->Form_FieldName($rootParents)) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ],
        ];

        if (!$this->_application->Map_Api()) {
            $ret += [
                'hide_timezone_if_no_map' => [
                    '#type' => 'checkbox',
                    '#title' => __('Hide timezone selection field', 'directories-pro'),
                    '#default_value' => !empty($settings['hide_timezone_if_no_map']),
                ],
            ];
            foreach (['map_height', 'center_latitude', 'center_longitude', 'zoom'] as $key) {
                $ret[$key]['#type'] = 'hidden';
            }
        }

        return $ret;
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $ret = array(
            // Group and add class for cloning the field
            '#group' => true,
            '#class' => 'drts-location-address-container',
            'location' => [
                'address' => [
                    '#type' => 'location_address',
                    '#map_type' => $this->_application->getComponent('Map')->getConfig('map', 'type'),
                    '#map_height' => $settings['map_height'],
                    '#center_latitude' => $settings['center_latitude'],
                    '#center_longitude' => $settings['center_longitude'],
                    '#zoom' => $settings['zoom'],
                    '#default_value' => $value,
                    '#weight' => 1,
                    '#hide_timezone_if_no_map' => !empty($settings['hide_timezone_if_no_map']),
                ],
            ],
        );

        if (!$field->isCustomField()
            && ($location_bundle = $this->_getLocationBundle($field))
            && ($taxonomy_select_widget = $this->_getSelectLocationForm(
                $location_bundle,
                $field,
                $value,
                $entity,
                $parents,
                $language,
                $taxonomy_select_disabled = !$this->_application->HasPermission('entity_assign_' . $location_bundle->name)))
        ) {
            if (!empty($settings['input_fields']['default'])) {
                $hierarchy = $this->_application->Location_Hierarchy($location_bundle);
                $ret['location']['address']['#input_fields'] = [];
                foreach ($settings['input_fields']['default'] as $key) {
                    if (!isset($hierarchy[$key]) // make sure it is not already selectable via taxonomy select dropdown field
                        && isset($settings['input_fields']['options'][$key])
                    ) {
                        $ret['location']['address']['#input_fields'][$key] = $this->_application->getPlatform()->translateString(
                            $settings['input_fields']['options'][$key],
                            'address_field_input_label_' . $key,
                            'location'
                        );
                    }
                }
            }
            $ret['location']['address']['#input_country'] = $settings['input_country'];
            $ret['location']['term_id'] = ['#weight' => 0] + $taxonomy_select_widget;
            $ret['#element_validate'] = [
                [
                    [$this, '_validateFormWithTerm'],
                    [
                        $taxonomy_select_disabled,
                        $entity && ($current_term = $entity->getSingleFieldValue($location_bundle->type)) ? $current_term->getId() : null
                    ]
                ],
            ];
        } else {
            $ret['location']['address']['#latlng_required_if_address'] = !empty($settings['latlng_required_if_address']);
            $ret['#element_validate'] = [
                [$this, '_validateForm'],
            ];
        }

        if (!empty($settings['allow_empty'])
            && !$field->isFieldRequired()
        ) {
            $item_label = $field->Bundle->getLabel('singular');
            $ret['location']['no_addr'] = [
                '#type' => 'checkbox',
                '#title' => sprintf(__('This %1$s does not have a physical location.', 'directories-pro'), strtolower($item_label), $item_label),
                '#weight' => -1,
                '#switch' => false,
                '#default_value' => isset($entity) && $value === null,
            ];
            $states = [
                'invisible' => [
                    'input[name="' . $this->_application->Form_FieldName(array_merge($parents, ['location', 'no_addr'])) . '[]"]' => ['type' => 'checked', 'value' => true],
                ],
            ];
            $ret['location']['address']['#states'] = $states;
            if (isset($ret['location']['term_id'])) {
                $ret['location']['term_id']['#states'] = $states;
            }
        }

        return $ret;
    }

    public function _maybeClearAddress(Form\Form $form, &$value, $element)
    {
        if (!empty($value['location']['no_addr'])) {
            $value = null;
        }
    }

    public function _validateForm(Form\Form $form, &$value, $element)
    {
        $this->_maybeClearAddress($form, $value, $element);
        if ($value === null) return;

        $value = $value['location']['address'];
    }

    public function _validateFormWithTerm(Form\Form $form, &$value, $element, $taxonomySelectDisabled, $currentTermId)
    {
        $this->_maybeClearAddress($form, $value, $element);
        if ($value === null) return;

        if ($taxonomySelectDisabled) {
            $term_id = $currentTermId;
        } else {
            $term_id = 0;
            if (!empty($value['location']['term_id'])) {
                while (null !== $_term_id = array_pop($value['location']['term_id'])) {
                    if ($_term_id !== '') {
                        $term_id = $_term_id;
                        break;
                    }
                }
            }
        }
        $value = $value['location']['address'] + array('term_id' => $term_id);
    }

    protected function _getSelectLocationForm($locationBundle, Field\IField $field, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null, $disabled = false)
    {
        if ((!$hierarchy = $this->_application->Location_Hierarchy($locationBundle))
            || (!$top_level_locations = $this->_getTopLevelLocations($locationBundle, $language))
        ) return;

        $default_text = __('— Select —', 'directories-pro');
        $hierarchy_keys = array_keys($hierarchy);
        $hierarchy_depth = count($hierarchy_keys);
        if (!empty($value['term_id'])
            && ($term_entity = $this->_application->Entity_Entity($locationBundle->entitytype_name, $value['term_id']))
        ) {
            $values = $this->_application->Entity_Types_impl($locationBundle->entitytype_name)->entityTypeParentEntityIds($term_entity);
            $values[] = $value['term_id'];
        } else {
            $values = [];
        }
        $disabled = !$this->_application->HasPermission('entity_assign_' . $locationBundle->name);
        $ret = array(
            array(
                '#type' => 'select',
                '#title' => $hierarchy[$hierarchy_keys[0]],
                '#horizontal' => true,
                '#weight' => 0,
                '#class' => 'drts-form-field-select-0',
                '#options' => array('' => $default_text) + $top_level_locations,
                '#multiple' => false,
                '#attributes' => array(
                    'class' => 'drts-location-term-select drts-location-find-address-component drts-form-selecthierarchical drts-location-address-' . $hierarchy_keys[0],
                ),
                '#default_value' => isset($values[0]) ? $values[0] : null,
                '#disabled' => $disabled,
                '#empty_value' => '',
            )
        );
        $load_options_url = $this->_application->MainUrl(
            '/_drts/entity/' . $locationBundle->type . '/taxonomy_terms',
            array('bundle' => $locationBundle->name, Request::PARAM_CONTENT_TYPE => 'json', 'language' => $language, 'depth' => 1)
        );
        for ($i = 1; $i < $hierarchy_depth; $i++) {
            $parent_dropdown_selector = sprintf('.drts-form-field-select-%d select', $i - 1);
            $ret[] = array(
                '#type' => 'select',
                '#title' => $hierarchy[$hierarchy_keys[$i]],
                '#horizontal' => true,
                '#multiple' => false,
                '#class' => 'drts-form-field-select-' . $i,
                '#hidden' => true,
                '#attributes' => array(
                    'data-load-url' => $load_options_url,
                    'data-options-prefix' => '',
                    'class' => 'drts-location-term-select drts-location-find-address-component drts-form-selecthierarchical drts-location-address-' . $hierarchy_keys[$i],
                ),
                '#default_value' => isset($values[$i]) ? $values[$i] : null,
                '#states' => array(
                    'load_options' => array(
                        $parent_dropdown_selector => array('type' => 'selected', 'value' => true, 'container' => '.drts-location-address-container'),
                    ),
                ),
                '#options' => array('' => $default_text),
                '#states_selector' => '.drts-form-field-select-' . $i,
                '#skip_validate_option' => true,
                '#weight' => $i,
                '#disabled' => $disabled,
                '#required' => false,
            );
        }

        return $ret;
    }

    protected function _getLocationBundle(Field\IField $field)
    {
        if (!isset($field->Bundle->info['taxonomies']['location_location'])) return;

        return $this->_application->Entity_Bundle($field->Bundle->info['taxonomies']['location_location']);
    }

    protected function _getTopLevelLocations($bundle, $language = null)
    {
        $ret = [];
        $terms = $this->_application->Entity_TaxonomyTerms($bundle->name, null, 0, $language);
        if (!empty($terms[0])) {
            foreach (array_keys($terms[0]) as $term_id) {
                $ret[$term_id] = [
                    '#title' => $terms[0][$term_id]['title'],
                    '#attributes' => ['data-alt-value' => $terms[0][$term_id]['name']],
                ];
            }
        }
        return $ret;
    }

    protected function _hasTopLevelLocations($bundle)
    {
        $terms = $this->_application->Entity_TaxonomyTerms($bundle->name, null, 0);
        return !empty($terms[0]);
    }
}
