<?php
namespace SabaiApps\Directories\Component\Search\Field;

use SabaiApps\Directories\Component\Entity;

class KeywordField extends AbstractField
{   
    protected function _searchFieldInfo()
    {
        return array(
            'label' => __('Keyword Search', 'directories'),
            'weight' => 1,
            'default_settings' => array(
                'min_length' => 2,
                'match' => 'all',
                'child_bundle_types' => null,
                'taxonomies' => null,
                'extra_fields' => null,
                'suggest' => true,
                'suggest_min_length' => 2,
                'suggest_post_num' => 5,
                'suggest_post_jump' => true,
                'form' => array(
                    'icon' => 'fas fa-search',
                    'placeholder' => __('Search for...', 'directories'),
                    'order' => 1,
                ),
            ),
        );
    }
    
    public function searchFieldSupports(Entity\Model\Bundle $bundle)
    {
        return empty($bundle->info['parent']);
    }
    
    public function searchFieldSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $form = array(
            'min_length' => array(
                '#type' => 'slider',
                '#title' => __('Min. length of keywords in characters', 'directories'),
                '#default_value' => $settings['min_length'],
                '#integer' => true,
                '#min_value' => 1,
                '#max_value' => 10,
                '#horizontal' => true,
                '#weight' => 1,
            ),
            'match' => array(
                '#type' => 'select',
                '#title' => __('Default match type', 'directories'),
                '#options' => array(
                    'any' => __('Match any', 'directories'),
                    'all' => __('Match all', 'directories'),
                ),
                '#default_value' => $settings['match'],
                '#horizontal' => true,
                '#weight' => 2,
            ),
        );
            
        $child_bundle_types = [];
        foreach ($this->_application->Entity_BundleTypes_children($bundle->type) as $child_bundle_type) {
            if (!$child_bundle = $this->_application->Entity_Bundle($child_bundle_type, $bundle->component, $bundle->group)) continue;
            
            $child_bundle_types[$child_bundle_type] = $child_bundle->getLabel();
        }
        if (!empty($child_bundle_types)) {
            $form['child_bundle_types'] = array(
                '#type' => 'checkboxes',
                '#title' => __('Search child content items', 'directories'),
                '#options' => $child_bundle_types,
                '#default_value' => $settings['child_bundle_types'],
                '#horizontal' => true,
                '#weight' => 5,
            );
        }
        if (!empty($bundle->info['taxonomies'])) {
            $form['taxonomies'] = array(
                '#type' => 'checkboxes',
                '#title' => __('Search taxonomy term names', 'directories'),
                '#options' => [],
                '#default_value' => $settings['taxonomies'],
                '#horizontal' => true,
                '#weight' => 6,
                '#description' => __('WARNING! This could slow down the performance of search considerably when there are a large number of taxonomy terms.', 'directories'),
            );
        }
            
        // Add extra fields to include in search
        $searchable_fields = array('string', 'text', 'choice', 'number');
        if ($fields = $this->_application->Entity_Field($bundle)) {
            $extra_field_options = [];
            foreach ($fields as $field_name => $field) {
                if ($field->isPropertyField()
                    || $field_name === 'content_body'
                ) continue;

                if (in_array($field->getFieldType(), $searchable_fields)) {
                    $extra_field_options[$field_name] = $field->getFieldLabel() . ' - ' . $field_name;
                }
            }
        }
        if (!empty($extra_field_options)) {
            asort($extra_field_options);
            $form['extra_fields'] = array(
                '#type' => 'checkboxes',
                '#title' => __('Extra fields to include in search', 'directories'),
                '#default_value' => $settings['extra_fields'],
                '#options' => $extra_field_options,
                '#weight' => 15,
                '#horizontal' => true,
            );
        }
            
        $suggest_prefix = $this->_application->Form_FieldName(array_merge($parents, array('suggest', 'enable')));
        $suggest_post_states = array(
            'visible' => array(
                'input[name="' . $suggest_prefix . '"]' => array('type' => 'checked', 'value' => 1),
            ),
        );
        $form += array(
            'suggest' => array(
                '#title' => __('Auto-Suggest Settings', 'directories'),
                '#weight' => 15,
                '#class' => 'drts-form-label-lg',
                'enable' => array(
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['suggest']['enable']),
                    '#title' => __('Enable auto suggestions', 'directories'),
                    '#horizontal' => true,
                ),
                'settings' => array(
                    '#states' => $suggest_post_states,
                    'min_length' => array(
                        '#type' => 'slider',
                        '#title' => __('Minimum character length needed before triggering auto suggestions', 'directories'),
                        '#default_value' => $settings['suggest']['settings']['min_length'],
                        '#integer' => true,
                        '#min_value' => 1,
                        '#max_value' => 10,
                        '#states' => $suggest_post_states,
                        '#horizontal' => true,
                    ),
                    'post_jump' => array(
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['suggest']['settings']['post_jump']),
                        '#title' => __('Redirect to suggested post page when clicked', 'directories'),
                        '#states' => $suggest_post_states,
                        '#horizontal' => true,
                    ),
                    'post_num' => array(
                        '#type' => 'slider',
                        '#min_value' => 1,
                        '#max_value' => 10,
                        '#title' => __('Number of auto suggested posts to display', 'directories'),
                        '#integer' => true,
                        '#default_value' => $settings['suggest']['settings']['post_num'],
                        '#horizontal' => true,
                    ),
                ),
            ),
        );

        foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $taxonomy_name) {
            if (!$taxonomy_bundle = $this->_application->Entity_Bundle($taxonomy_name)) continue;
            
            $form['taxonomies']['#options'][$taxonomy_name] = $taxonomy_bundle->getLabel();
            $taxonomy_label = $taxonomy_bundle->getLabel('singular');
            $is_hierarchical = !empty($taxonomy_bundle->info['is_hierarchical']);
            $suggest_taxonomy_states = array(
                'visible' => array(
                    'input[name="' . $suggest_prefix . '"]' => array('value' => 1),
                    sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('suggest', $taxonomy_bundle_type)))) => array('value' => 1),
                ),
            );
            $form['suggest'] += array(
                $taxonomy_bundle_type => array(
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type]),
                    '#title' => $taxonomy_label . ' - ' . __('Enable auto suggestions', 'directories'),
                    '#states' => array(
                        'visible' => array(
                            'input[name="' . $suggest_prefix . '"]' => array('type' => 'checked', 'value' => 1),
                        ),
                    ),
                    '#horizontal' => true,
                ),
                $taxonomy_bundle_type . '_num' => array(
                    '#type' => 'slider',
                    '#min_value' => 1,
                    '#max_value' => 10,
                    '#title' => $taxonomy_label . ' - ' . __('Number of auto suggested terms to display', 'directories'),
                    '#integer' => true,
                    '#default_value' => isset($settings['suggest'][$taxonomy_bundle_type . '_num']) ? $settings['suggest'][$taxonomy_bundle_type . '_num'] : 3,
                    '#states' => $suggest_taxonomy_states,
                    '#horizontal' => true,
                ),
                $taxonomy_bundle_type . '_hide_empty' => array(
                    '#type' => 'checkbox',
                    '#title' => $taxonomy_label . ' - ' . __('Hide empty terms', 'directories'),
                    '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type . '_hide_empty']),
                    '#horizontal' => true,
                    '#states' => $suggest_taxonomy_states,
                ),
                $taxonomy_bundle_type . '_hide_count' => array(
                    '#type' => 'checkbox',
                    '#title' => $taxonomy_label . ' - ' . __('Hide post counts', 'directories'),
                    '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type . '_hide_count']),
                    '#horizontal' => true,
                    '#states' => $suggest_taxonomy_states,
                ),    
            );
            if ($is_hierarchical) {
                $form['suggest'] += array(
                    $taxonomy_bundle_type . '_depth' => array(
                        '#type' => 'slider',
                        '#title' => $taxonomy_label . ' - ' . __('Depth of term hierarchy tree', 'directories'),
                        '#default_value' => isset($settings['suggest'][$taxonomy_bundle_type . '_depth']) ? $settings['suggest'][$taxonomy_bundle_type . '_depth'] : 1,
                        '#min_value' => 0,
                        '#max_value' => 10,
                        '#min_text' => __('Unlimited', 'directories'),
                        '#integer' => true,
                        '#horizontal' => true,
                        '#states' => $suggest_taxonomy_states,
                    ),
                    $taxonomy_bundle_type . '_inc_parents' => array(
                        '#type' => 'checkbox',
                        '#title' => $taxonomy_label . ' - ' . __('Include parent term paths in term title', 'directories'),
                        '#default_value' => !empty($settings['suggest'][$taxonomy_bundle_type . '_inc_parents']),
                        '#horizontal' => true,
                        '#states' => $suggest_taxonomy_states,
                    ),
                );
            }
        }
            
        return $form;
    }
    
    public function searchFieldForm(Entity\Model\Bundle $bundle, array $settings, $request = null, array $requests = null, array $parents = [])
    {        
        $data = [];

        // Enable auto suggestions?
        if (!empty($settings['suggest']['enable'])) {
            $data['suggest-post'] = 'true';
            $data['suggest-post-url'] = $this->_getSuggestUrl($bundle, $settings['suggest']['settings']['post_num'], 'QUERY');
            $data['suggest-post-icon'] = $this->_application->Entity_BundleTypeInfo($bundle->type, 'icon');
            $data['suggest-post-jump'] = empty($settings['suggest']['settings']['post_jump']) ? 'false' : 'true';
            $data['suggest-min-length'] = $settings['suggest']['settings']['min_length'];
            $data['suggest-post-prefetch-url'] = $this->_getSuggestUrl($bundle, 100);

            //$data['suggest-post-header'] = $bundle->getLabel('singular');
            $taxonomies = $bundle->info['taxonomies'];
            foreach ($taxonomies as $taxonomy_bundle_type => $taxonomy_bundle_name) {
                if (!empty($settings['suggest'][$taxonomy_bundle_type])
                    && ($taxonomy_bundle = $this->_application->Entity_Bundle($taxonomy_bundle_name))
                ) {
                    $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-url'] = $this->_getSuggestTaxonomyUrl(
                        $taxonomy_bundle_type,
                        array($taxonomies[$taxonomy_bundle_type]),
                        isset($settings['suggest'][$taxonomy_bundle_type . '_depth']) ? (int)$settings['suggest'][$taxonomy_bundle_type . '_depth'] : null,
                        !empty($settings['suggest'][$taxonomy_bundle_type . '_hide_empty'])
                    );
                    $data['suggest-taxonomy-top-' . $taxonomy_bundle_type . '-url'] = $this->_getSuggestTaxonomyUrl(
                        $taxonomy_bundle_type,
                        array($taxonomies[$taxonomy_bundle_type]),
                        1,
                        !empty($settings['suggest'][$taxonomy_bundle_type . '_hide_empty'])
                    );
                    $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-header'] = $taxonomy_bundle->getLabel('singular');
                    $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-icon'] = $this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'icon');
                    $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-num'] = isset($settings['suggest'][$taxonomy_bundle_type . '_num']) ? $settings['suggest'][$taxonomy_bundle_type . '_num'] : 3;
                    if (empty($settings['suggest'][$taxonomy_bundle_type . '_hide_count'])) {
                        $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-count'] = $bundle->type;
                    }
                    $data['suggest-taxonomy-' . $taxonomy_bundle_type . '-parents'] = empty($settings['suggest'][$taxonomy_bundle_type . '_inc_parents']) ? 'false' : 'true';
                } else {
                    unset($taxonomies[$taxonomy_bundle_type]);
                }
            }
            $data['suggest-taxonomy'] = implode(',', array_keys($taxonomies));
        }
        
        $form = array(
            '#data' => $data,
            '#default_value' => $request,
            'text' => array(
                '#type' => 'textfield',
                '#placeholder' => $settings['form']['placeholder'],
                '#data' => array('clear-placeholder' => 1),
                '#attributes' => array(
                    'class' => 'drts-search-keyword-text',
                    'autocomplete' => 'off',
                ),
                '#id' => '__FORM_ID__-search-keyword-text',
                '#add_clear' => true,
                '#field_prefix' => empty($settings['form']['icon']) ? null : '<label for="__FORM_ID__-search-keyword-text" class="' . $settings['form']['icon'] . '"></label>',
                '#field_prefix_no_addon' => true,
            ),
            'id' => array(
                '#type' => 'hidden',
                '#class' => 'drts-search-keyword-id',
            ),
            'taxonomy' => array(
                '#type' => 'hidden',
                '#class' => 'drts-search-keyword-taxonomy',
            ),
        );
        
        if (!empty($settings['suggest']['enable'])) {
            $form['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
            $form['#id'] = '__FORM_ID__-search-keyword';
            $form['#js_ready'] = 'DRTS.Search.keyword("#__FORM_ID__-search-keyword");';
        }
        
        return $form;
    }
    
    protected function _getSuggestUrl($bundle, $num, $query = null)
    {
        return $this->_application->MainUrl(
            '/_drts/entity/' . $bundle->type . '/query/' . $bundle->name . '.json',
            array(
                'query' => $query,
                'num' => $num,
            ),
            '',
            '&'
        );        
    }
    
    protected function _getSuggestTaxonomyUrl($taxonomyBundleType, $taxonomyBundles, $depth = null, $hideEmpty = false)
    {
        return $this->_application->MainUrl(
            '/_drts/entity/' . $taxonomyBundleType . '/taxonomy_terms/' . implode(',', $taxonomyBundles) . '.json',
            array(
                'depth' => empty($depth) ? null : $depth,
                'hide_empty' => $hideEmpty ? 1 : null,
                'no_url' => 1,
                'no_depth' => 1,
                'all_count_only' => 1,
            ),
            '',
            '&'
        );
    }
    
    public function searchFieldIsSearchable(Entity\Model\Bundle $bundle, array $settings, &$value, array $requests = null)
    {        
        // Allow request value sent as string instead of array
        if (is_string($value)) {
            $value = array('text' => $value);
        }
        
        if (!empty($value['id'])) {
            if (!empty($value['taxonomy'])) {
                if (!$this->_application->Entity_Bundle($value['taxonomy'], $bundle->component, $bundle->group)) {
                    return false;
                }
            }
            return true;
        }
        
        unset($value['id']);
        
        if (!isset($value['text'])
            || (!$value['text'] = trim((string)$value['text']))
        ) {
            return false;
        }
        
        $keywords = $this->_application->Keywords($value['text'], $settings['min_length']);
        
        if (empty($keywords[0])) return false; // no valid keywords
        
        $value['keywords'] = $keywords[0];
        
        return true;
    }
    
    public function searchFieldSearch(Entity\Model\Bundle $bundle, Entity\Type\Query $query, array $settings, $value, array &$sorts)
    {   
        if (!empty($value['id'])) {
            if (!empty($value['taxonomy'])) {
                $taxonomy_bundle = $this->_application->Entity_Bundle($value['taxonomy'], $bundle->component, $bundle->group);
                $query->taxonomyTermIdIs($taxonomy_bundle->type, $value['id'], (bool)$taxonomy_bundle->info['is_hierarchical']);
            } else {
                $query->fieldIs('id', $value['id']);
            }
            return;
        }
        
        // Search child content types?
        $on = null;
        if (!empty($settings['child_bundle_types'])) {
            $bundle_names = array($bundle->name);
            if ($child_bundles = $this->_application->Entity_Bundles($settings['child_bundle_types'], $bundle->component, $bundle->group)) {
                foreach (array_keys($child_bundles) as $child_bundle_name) {
                    $bundle_names[] = $child_bundle_name;
                }
            }
            $query->removeNamedCriteria('bundle_name')->fieldIsIn('bundle_name', $bundle_names);
            $entity_type_info = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeInfo();
            if (!empty($entity_type_info['properties']['parent'])) {
                $parent_prop = $entity_type_info['properties']['parent'];
                if (isset($parent_prop['field_name'])) {
                    // parent field is in another table
                    $on = 'entity_id = %3$s AND %1$s.entity_type = ' . $this->_application->getDB()->escapeString($bundle->entitytype_name);
                    $query->addTableJoin('entity_field_' . $parent_prop['field_name'], $parent_prop['field_name'], $on)
                        ->setTableIdColumn('COALESCE(NULLIF(' . $parent_prop['field_name'] . '.' . $parent_prop['column'] . ', 0), %s)');
                } else {
                    $query->setTableIdColumn('COALESCE(NULLIF(' . $parent_prop['column'] . ', 0), %s)');
                }
            }
        }
        
        if ($settings['match'] === 'any') {
            $query->startCriteriaGroup('OR');
            $this->_queryKeywords($bundle, $query, $value['keywords'], $on, $settings['extra_fields'], $settings['taxonomies']);
            $query->finishCriteriaGroup();
        } else {
            $this->_queryKeywords($bundle, $query, $value['keywords'], $on, $settings['extra_fields'], $settings['taxonomies']);
        }
    }
    
    protected function _queryKeywords(Entity\Model\Bundle $bundle, Entity\Type\Query $query, array $keywords, $on, array $extraFields = null, array $taxonomies = null)
    {
        foreach ($keywords as $keyword) {
            $query->startCriteriaGroup('OR')
                ->fieldContains('content', $keyword, 'value', null, $on) // need this to join content field table with child entities as well
                ->fieldContains('title', $keyword);
            if (!empty($extraFields)) {
                foreach ($extraFields as $field_name) {
                    if ($_field = $this->_application->Entity_Field($bundle, $field_name)) {
                        $query->fieldContains($_field, $keyword);
                    }
                }
            }
            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    $query->taxonomyTermTitleContains($taxonomy, $keyword);
                }
            }
            $query->finishCriteriaGroup();
        }
    }
    
    public function searchFieldLabel(Entity\Model\Bundle $bundle, array $settings, $value)
    {
        return empty($value['keywords']) ? $value['text'] : $value['keywords'];
    }
    
    public function preRenderCallback($form)
    {
        $this->_application->Form_LoadTypeAhead();
    }
}