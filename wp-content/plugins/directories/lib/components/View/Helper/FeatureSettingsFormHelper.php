<?php
namespace SabaiApps\Directories\Component\View\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;

class FeatureSettingsFormHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, array $settings = [], array $submitValues = null)
    {
        $form = [];
        foreach (['sort', 'pagination', 'query', 'filter', 'other'] as $feature_name) {
            if (!$feature_settings_form = $this->_getViewFeatureSettingsForm(
                $application,
                $bundle,
                $feature_name,
                isset($settings[$feature_name]) ? $settings[$feature_name] : [],
                [$feature_name],
                isset($submitValues[$feature_name]) ? $submitValues[$feature_name] : null
            )) continue;

            $form[$feature_name] = ['#tree' => true] + $feature_settings_form;
        }

        return $application->Filter('view_feature_settings_form', $form, [$bundle, $settings, $submitValues]);
    }

    protected function _getViewFeatureSettingsForm(Application $application, Entity\Model\Bundle $bundle, $feature, array $settings, array $parents, array $submitValues = null)
    {
        switch ($feature) {
            case 'sort':
                $sorts = $this->_getSortOptions($application, $bundle);
                $form = [
                    '#tabs' => [
                        $feature => [
                            '#title' => __('Sort Settings', 'directories'),
                            '#weight' => 5,
                        ],
                    ],
                    '#tab' => $feature,
                    '#element_validate' => array(array($this, '_validateSort')),
                    'default' => array(
                        '#type' => 'select',
                        '#default_value' => isset($settings['default']) ? $settings['default'] : null,
                        '#title' => __('Default sort order', 'directories'),
                        '#options' => $sorts,
                        '#required' => true,
                        '#display_unrequired' => true,
                        '#horizontal' => true,
                        '#weight' => 5,
                    ),
                    'options' => array(
                        '#type' => 'sortablecheckboxes',
                        '#options' => $sorts,
                        '#default_value' => isset($settings['options']) ? $settings['options'] : array(current(array_keys($sorts))),
                        '#title' => __('Sort options', 'directories'),
                        '#horizontal' => true,
                        '#weight' => 1,
                    ),
                ];
                if ($application->Entity_BundleTypeInfo($bundle, 'featurable')) {
                    $form['stick_featured'] = [
                        '#type' => 'checkbox',
                        '#title' => __('Show featured items first', 'directories'),
                        '#default_value' => !empty($settings['stick_featured']),
                        '#horizontal' => true,
                        '#weight' => 10,
                    ];
                }
                return $form;

            case 'pagination':
                return [
                    '#tabs' => [
                        $feature => [
                            '#title' => _x('Pagination', 'tab', 'directories'),
                            '#weight' => 10,
                        ],
                    ],
                    '#tab' => $feature,
                    'no_pagination' => [
                        '#type' => 'checkbox',
                        '#title' => __('Disable pagination', 'directories'),
                        '#default_value' => !empty($settings['no_pagination']),
                        '#horizontal' => true,
                        '#weight' => 1,
                    ],
                    'perpage' => [
                        '#type' => 'slider',
                        '#title' => __('Items per page', 'directories'),
                        '#default_value' => isset($settings['perpage']) ? $settings['perpage'] : 20,
                        '#integer' => true,
                        '#required' => true,
                        '#display_unrequired' => true,
                        '#max_value' => 120,
                        '#min_value' => 1,
                        '#horizontal' => true,
                        '#states' => array(
                            'invisible' => array(
                                sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, array('no_pagination')))) => array('type' => 'checked', 'value' => true)
                            ),
                        ),
                        '#weight' => 5,
                    ],
                    'allow_perpage' => [
                        '#type' => 'checkbox',
                        '#title' => __('Allow selection of number of items per page', 'directories'),
                        '#default_value' => !empty($settings['allow_perpage']),
                        '#horizontal' => true,
                        '#states' => array(
                            'invisible' => array(
                                sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, array('no_pagination')))) => array('type' => 'checked', 'value' => true)
                            ),
                        ),
                        '#weight' => 10,
                    ],
                    'perpages' => [
                        '#type' => 'checkboxes',
                        '#integer' => true,
                        '#title' => __('Allowed number of items per page', 'directories'),
                        '#default_value' => isset($settings['perpages']) ? $settings['perpages'] : array(10, 20, 50),
                        '#options' => array_combine($perpages = $application->Filter('view_pagination_perpages', array(10, 12, 15, 20, 24, 30, 36, 48, 50, 60, 100, 120, 200)), $perpages),
                        '#horizontal' => true,
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, array('no_pagination')))) => array('type' => 'checked', 'value' => false),
                                sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, array('allow_perpage')))) => array('type' => 'checked', 'value' => true)
                            ),
                        ),
                        '#weight' => 15,
                        '#columns' => 6,
                    ],
                ];
            case 'filter':
                if (!$application->getComponent('View')->isFilterable($bundle)) return;

                $show_filters_selector = sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['show'])));
                $show_in_modal_selector = sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['show_modal'])));
                $ret = [
                    '#tabs' => [
                        $feature => [
                            '#title' => __('Filter Settings', 'directories'),
                            '#weight' => 15,
                        ],
                    ],
                    '#tab' => $feature,
                    '#element_validate' => array(array($this, '_validateFilter')),
                    'show' => [
                        '#type' => 'checkbox',
                        '#title' => __('Enable filters', 'directories'),
                        '#default_value' => !empty($settings['show']),
                        '#horizontal' => true,
                        '#weight' => 5,
                    ],
                    'shown' => [
                        '#type' => 'checkbox',
                        '#title' => __('Show filters by default', 'directories'),
                        '#default_value' => !empty($settings['shown']),
                        '#horizontal' => true,
                        '#weight' => 11,
                        '#states' => [
                            'visible' => [
                                $show_filters_selector => ['type' => 'checked', 'value' => true],
                                $show_in_modal_selector => ['type' => 'checked', 'value' => false]
                            ],
                        ],
                    ],
                    'auto_submit' => [
                        '#title' => __('Auto submit filter form', 'directories'),
                        '#type' => 'checkbox',
                        '#default_value' => !isset($settings['auto_submit']) || $settings['auto_submit'],
                        '#horizontal' => true,
                        '#weight' => 15,
                        '#states' => [
                            'visible' => [
                                $show_filters_selector => ['type' => 'checked', 'value' => true],
                                $show_in_modal_selector => ['type' => 'checked', 'value' => false]
                            ],
                        ],
                    ],
                ];
                if (empty($bundle->info['parent'])) {
                    $ret['show_modal'] = [
                        '#type' => 'checkbox',
                        '#title' => __('Show filters in modal window', 'directories'),
                        '#default_value' => !empty($settings['show_modal']),
                        '#horizontal' => true,
                        '#weight' => 10,
                        '#states' => [
                            'visible' => [
                                $show_filters_selector => ['type' => 'checked', 'value' => true],
                            ],
                        ],
                    ];
                }

                return $ret;

            case 'query':
                $fields = $application->Entity_Field($bundle);
                $form = array(
                    '#tabs' => [
                        $feature => [
                            '#title' => __('Query Settings', 'directories'),
                            '#weight' => 20,
                        ],
                    ],
                    '#tab' => $feature,
                    '#element_validate' => array(array($this, '_validateQuery')),
                    'fields' => array(
                        '#title' => __('Query by field', 'directories'),
                        '#horizontal' => true,
                        '#weight' => 5,
                    ),
                    'limit' => array(
                        '#type' => 'number',
                        '#title' => __('Max number of items to query (0 = unlimited)', 'directories'),
                        '#default_value' => empty($settings['limit']) ? 0 : (int)$settings['limit'],
                        '#horizontal' => true,
                        '#integer' => true,
                        '#weight' => 10,
                    )
                );
                if (isset($submitValues['fields'])) {
                    // coming from form submission
                    // need to check request values since fields may have been added/removed
                    $queries = empty($submitValues['fields']) ? array(null) : $submitValues['fields'];
                } else {
                    $queries = [];
                    if (!empty($settings['fields'])) {
                        foreach ($settings['fields'] as $field_name => $query_str) {
                            $queries[] = array('field' => $field_name, 'query' => $query_str);
                        }
                    }
                    $queries[] = null;
                }
                foreach ($queries as $i => $query) {
                    $form['fields'][$i] = array(
                        '#type' => 'field_query',
                        '#fields' => $fields,
                        '#default_value' => $query,
                    );
                }
                $form['fields']['_add'] = array(
                    '#type' => 'addmore',
                    '#next_index' => ++$i,
                );
                if (!empty($bundle->info['privatable'])) {
                    $form['exclude_private'] = array(
                        '#type' => 'checkbox',
                        '#title' => __('Exclude private items', 'directories'),
                        '#default_value' => !empty($settings['exclude_private']),
                        '#horizontal' => true,
                        '#weight' => 20,
                    );
                }

                return $form;

            case 'other':
                $form = [
                    '#tabs' => [
                        $feature => [
                            '#title' => __('Other', 'directories'),
                            '#weight' => 99,
                        ],
                    ],
                    '#tab' => $feature,
                    'num' => [
                        '#type' => 'checkbox',
                        '#title' => __('Show number of items found', 'directories'),
                        '#default_value' => !empty($settings['num']),
                        '#horizontal' => true,
                        '#weight' => 5,
                    ],
                ];
                if (empty($bundle->info['is_taxonomy'])
                    && !empty($bundle->info['public'])
                    && empty($bundle->info['parent'])
                    && $application->isComponentLoaded('FrontendSubmit')
                ) {
                    $form['add'] = [
                        'show' => [
                            '#type' => 'checkbox',
                            '#title' => sprintf(__('Show "%s" button', 'directories'), $bundle->getLabel('add')),
                            '#default_value' => !empty($settings['add']['show']),
                            '#horizontal' => true,
                        ],
                        'show_label' => [
                            '#type' => 'checkbox',
                            '#title' => sprintf(__('Show "%s" button with label', 'directories'), $bundle->getLabel('add')),
                            '#default_value' => !empty($settings['add']['show_label']),
                            '#horizontal' => true,
                            '#states' => array(
                                'visible' => array(
                                    sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, array('add', 'show')))) => array('type' => 'checked', 'value' => true)
                                ),
                            ),
                        ],
                        '#weight' => 10,
                    ];
                }
                return $form;
        }
    }

    protected function _getSortOptions(Application $application, $bundle)
    {
        $ret = [];
        foreach ($application->Entity_Sorts($bundle->name) as $sort_name => $sort) {
            $ret[$sort_name] = $sort['label'];
        }

        return $ret;
    }

    public function _validateFilter(Form\Form $form, &$value, $element)
    {
        if (!empty($value['show_modal'])) {
            $value['shown'] = false;
        }
    }

    public function _validateSort(Form\Form $form, &$value, $element)
    {
        if (!empty($value['default'])) {
            if (empty($value['options'])) {
                $value['options'] = [];
            }
            if (!in_array($value['default'], $value['options'])) {
                $value['options'][] = $value['default'];
            }
        }
    }

    public function _validateQuery(Form\Form $form, &$value, $element)
    {
        if (empty($value['fields'])) return;

        $queries = [];
        foreach (array_filter($value['fields']) as $query) {
            if (!strlen($query['field'])
                || !strlen(trim($query['query']))
            ) continue;

            $queries[$query['field']] = $query['query'];
        }
        $value['fields'] = $queries;
    }
}
