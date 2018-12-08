<?php
namespace SabaiApps\Directories\Component\Search\Field;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class TermField extends AbstractField
{
    protected $_bundleType;
    
    public function __construct(Application $application, $name, $bundleType)
    {
        parent::__construct($application, $name);
        $this->_bundleType = $bundleType;
    }
    
    protected function _searchFieldInfo()
    {
        return array(
            'label' => sprintf(
                _x('%s Selection Search', 'search settings label', 'directories'),
                $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'label_singular')
            ),
            'weight' => 3,
            'default_settings' => array(
                'disabled' => false,
                'hide_empty' => false,
                'hide_count' => false,
                'depth' => 0,
                'form' => array(
                    'order' => 3,
                ),
            ),
        );
    }
    
    public function searchFieldSupports(Entity\Model\Bundle $bundle)
    {
        return isset($bundle->info['taxonomies'][$this->_bundleType]);
    }
    
    public function searchFieldSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $ret = array(
            'depth' => array(
                '#type' => 'slider',
                '#title' => __('Depth of term hierarchy tree', 'directories'),
                '#default_value' => $settings['depth'],
                '#min_value' => 0,
                '#max_value' => 10,
                '#min_text' => __('Unlimited', 'directories'),
                '#integer' => true,
                '#weight' => 1,
                '#horizontal' => true,
            ),
            'hide_empty' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide empty terms', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#horizontal' => true,
                '#weight' => 6,
            ),
            'hide_count' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide post counts', 'directories'),
                '#default_value' => !empty($settings['hide_count']),
                '#horizontal' => true,
                '#weight' => 7,
            ),
        );
        
        return $ret;
    }
    
    public function searchFieldForm(Entity\Model\Bundle $bundle, array $settings, $request = null, array $requests = null, array $parents = [])
    {
        $current_term_id = isset($GLOBALS['drts_entity']) && $GLOBALS['drts_entity']->getBundleType() === $this->_bundleType ? $GLOBALS['drts_entity']->getId() : null;
        $_options = array(
            'depth' => $settings['depth'],
            'hide_empty' => !empty($settings['hide_empty']),
            'hide_count' => !empty($settings['hide_count']),
            'prefix' => '—',
            'parent' => isset($settings['parent']) ? (int)$settings['parent'] : 0
        );

        $taxonomy_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group);
        $options = $this->_application->Entity_TaxonomyTerms_html(
            $taxonomy_bundle->name,
            array('content_bundle' => $bundle->type, 'count_no_html' => true) + $_options
        );
        if (!count($options)) return;
                
        $optgroups = $optgroups_attr = null;
        $default_text = $taxonomy_bundle->getLabel('select');
        $attributes = array('data-component' => $bundle->component);
        
        $form = array(
            '#type' => 'select',
            '#select2' => true,
            '#placeholder' => $default_text,
            '#optgroups' => $optgroups,
            '#optgroups_attr' => $optgroups_attr,
            '#options' => array('' => $default_text) + $options,
            '#default_value' => isset($current_term_id) ? $current_term_id : $request,
            '#attributes' => isset($attributes) ? $attributes : [],
            //'#bootstrap' => array(
            //    'selected-text-format' => 'count',
            //    'dropdown-align-right' => !empty($settings['right']),
            //),
        );
        //if (!empty($settings['multiple'])) {
        //    $ret['#multiple'] = true;
        //    $ret['#options'] = $options;
        //    $ret['#attributes']['data-none-selected-text'] = $default_text;
        //}
        
        return $form;
    }
    
    public function searchFieldIsSearchable(Entity\Model\Bundle $bundle, array $settings, &$value, array $requests = null)
    {
        return $value !== '';
    }
    
    public function searchFieldSearch(Entity\Model\Bundle $bundle, Entity\Type\Query $query, array $settings, $value, array &$sorts)
    {
        $query->taxonomyTermIdIs($this->_bundleType, $value, $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'is_hierarchical'));
    }
    
    public function searchFieldLabel(Entity\Model\Bundle $bundle, array $settings, $value)
    {
        return $this->_application->Entity_Entity($this->_application->Entity_BundleTypeInfo($this->_bundleType, 'entity_type'), $value, false)->getTitle();
    }
}