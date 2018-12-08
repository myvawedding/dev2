<?php
namespace SabaiApps\Directories\Component\Location\SearchField;

use SabaiApps\Directories\Component\Search\Field\AbstractField;
use SabaiApps\Directories\Component\Entity;

class AddressSearchField extends AbstractField
{
    protected function _searchFieldInfo()
    {
        return array(
            'label' => __('Location Search', 'directories-pro'),
            'weight' => 2,
            'default_settings' => array(
                'geolocation' => true,
                'radius' => 0,
                'disable_radius' => true,
                'suggest' => array(
                    'enable' => true,
                    'settings' => array(
                        'hide_empty' => false,
                        'hide_count' => false,
                        'inc_parents' => true,
                        'depth' => 0,
                    ),
                ),
                'form' => array(
                    'icon' => 'fas fa-map-marker-alt',
                    'placeholder' => _x('Near...', 'search form', 'directories-pro'),
                    'order' => 2,
                ),
            ),
        );
    }
    
    public function searchFieldSupports(Entity\Model\Bundle $bundle)
    {
        return !empty($bundle->info['location_enable']);
    }
    
    public function searchFieldSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {          
        return array(
            'radius' => array(
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => 100,
                '#min_text' => __('Auto', 'directories-pro'),
                '#field_suffix' => $this->_application->getComponent('Map')->getConfig('map', 'distance_unit'),
                '#title' => __('Default search radius', 'directories-pro'),
                '#default_value' => $settings['radius'],
                '#horizontal' => true,
                '#description' => __('Select "Auto" to let the map API calculate the optimal search radius based on the location value entered in the field.', 'directories-pro'),
            ),
            'disable_radius' => array(
                '#type' => 'checkbox',
                '#title' => __('Disable search radius selection', 'directories-pro'),
                '#default_value' => !empty($settings['disable_radius']),
                '#horizontal' => true,
            ),
            'geolocation' => array(
                '#type' => 'checkbox',
                '#title' => __("Enable search by user's current location", 'directories-pro'),
                '#default_value' => !empty($settings['geolocation']),
                '#horizontal' => true,
            ),
            'suggest' => array(
                '#title' => __('Auto-Suggest Settings', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                'enable' => array(
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['suggest']['enable']),
                    '#title' => __('Auto-suggest terms', 'directories-pro'),
                    '#horizontal' => true,
                ),
                'settings' => array(
                    '#states' => array(
                        'visible' => array(
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('suggest', 'enable')))) => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                    'depth' => array(
                        '#type' => 'slider',
                        '#title' => __('Depth of term hierarchy tree', 'directories-pro'),
                        '#min_text' => __('Unlimited', 'directories-pro'),
                        '#default_value' => $settings['suggest']['settings']['depth'],
                        '#min_value' => 0,
                        '#max_value' => 10,
                        '#integer' => true,
                        '#horizontal' => true,
                    ),
                    'hide_empty' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Hide empty terms', 'directories-pro'),
                        '#default_value' => !empty($settings['suggest']['settings']['hide_empty']),
                        '#horizontal' => true,
                    ),
                    'hide_count' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Hide post counts', 'directories-pro'),
                        '#default_value' => !empty($settings['suggest']['settings']['hide_count']),
                        '#horizontal' => true,
                    ),
                    'inc_parents' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Include parent term paths in term title', 'directories-pro'),
                        '#default_value' => !empty($settings['suggest']['settings']['inc_parents']),
                        '#horizontal' => true,
                    ),
                ),
            ),
        );
    }
    
    public function searchFieldForm(Entity\Model\Bundle $bundle, array $settings, $request = null, array $requests = null, array $parents = [])
    {        
        $form = array(
            '#type' => 'location_text',
            '#default_value' => $request,
            '#radius' => $settings['radius'],
            '#disable_radius' => !empty($settings['disable_radius']),
            '#placeholder' => $settings['form']['placeholder'],
            '#data' => array('clear-placeholder' => 1),
            '#geolocation' => !empty($settings['geolocation']),
            '#settings_max_radius' => 100,
        );
        if (!empty($settings['form']['icon'])) {
            $form['#text_field_prefix'] = '<label for="__FORM_ID__-location-search-address-text" class="' . $settings['form']['icon'] . '"></label>';
            $form['#text_id'] = '__FORM_ID__-location-search-address-text';
        }

        if (!empty($settings['suggest']['enable'])) {
            if ($taxonomy_bundle = $this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group)) {
                $form['#suggest_location'] = $taxonomy_bundle->name;
                $form['#suggest_location_url'] = $this->_getSuggestTaxonomyUrl(array($taxonomy_bundle->name), $settings['suggest']['settings']);
                $form['#suggest_location_count'] = empty($settings['suggest']['settings']['hide_count']) ? '_' . $bundle->type : false;
                $form['#suggest_location_parents'] = !empty($settings['suggest']['settings']['inc_parents']);
                //$form['#suggest_location_header'] = $taxonomy_bundle->getLabel('singular');
                $form['#suggest_location_icon'] = $this->_application->Entity_BundleTypeInfo($taxonomy_bundle->type, 'icon');
            }
        }

        return $form;
    }
    
    public function searchFieldIsSearchable(Entity\Model\Bundle $bundle, array $settings, &$value, array $requests = null)
    {
        return false !== ($value = $this->_application->Location_FilterField_preFilter($value, $settings['radius']));
    }
    
    public function searchFieldSearch(Entity\Model\Bundle $bundle, Entity\Type\Query $query, array $settings, $value, array &$sorts)
    {        
        if (!$field = $this->_application->Entity_Field($bundle, 'location_address')) return;
                
        $this->_application->callHelper(
            'Location_FilterField',
            array($field, $query->getFieldQuery(), $value, array('default_radius' => $settings['radius']), &$sorts)
        );
    }
    
    public function searchFieldLabel(Entity\Model\Bundle $bundle, array $settings, $value)
    {
        return $value['text'];
    }
    
    protected function _getSuggestTaxonomyUrl(array $taxonomyBundles, array $settings)
    {
        return $this->_application->MainUrl(
            '/_drts/entity/location_location/taxonomy_terms/' . implode(',', $taxonomyBundles) . '.json',
            array(
                'depth' => empty($settings['depth']) ? null : (int)$settings['depth'],
                'hide_empty' => empty($settings['hide_empty']) ? null : 1,
                'no_url' => 1,
                'no_depth' => 1,
                'all_count_only' => 1,
            ),
            '',
            '&'
        );
    }
}