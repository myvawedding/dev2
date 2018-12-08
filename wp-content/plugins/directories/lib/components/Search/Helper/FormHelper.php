<?php
namespace SabaiApps\Directories\Component\Search\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Search\SearchComponent;
use SabaiApps\Directories\Component\Form\FormComponent;
use SabaiApps\Directories\Context;

class FormHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, array $values = [], array $displaySettings = [], $route = null, $submitCallback = null)
    {
        $displaySettings += [
            'btn_text' => __('Search', 'directories'),
            'btn_icon' => '',
            'btn_color' => 'primary',
            'size' => '',
            'style' => '',
            'fields' => null,
        ];

        $search_fields = empty($bundle->info['search_fields']) ? [] : $bundle->info['search_fields'];
        $weight = 0;
        $form = [
            '#action' => isset($route) ? $application->Url($route) : null,
            '#method' => 'post',
            '#token' => false,
            '#build_id' => false,
            '#class' => 'drts-search-form drts-form-group-no-margin',
            '#field_names' => [],
            '#attributes' => ['style' => $displaySettings['style']],
            'prefix' => [
                '#type' => 'markup',
                '#markup' => '<div class="drts-row drts-gutter-none">',
                '#weight' => ++$weight,
            ],
            'buttons' => [
                '#tree' => false,
                '#weight' => 99,
                FormComponent::FORM_SUBMIT_BUTTON_NAME => [
                    '#type' => 'submit',
                    '#btn_color' => $btn_color = $displaySettings['btn_color'],
                    '#btn_label' => $this->_getButtonText($application, $displaySettings['btn_text'], $displaySettings['btn_icon']),
                    '#btn_label_noescape' => true,
                    '#attributes' => array('class' => 'drts-search-form-submit'),
                    '#submit' => !isset($submitCallback) ? null : [
                        0 => [ // weight
                            $submitCallback,
                        ],
                    ],
                ],
            ],
            'suffix' => [
                '#type' => 'markup',
                '#markup' => '</div></div></div>',
                '#weight' => 100,
            ],
        ];
        if ($displaySettings['size'] === 'lg') {
            $form['#class'] .= ' drts-search-form-lg';
        }
        unset($displaySettings['size'], $displaySettings['style'], $displaySettings['btn_color'], $displaySettings['btn_text'], $displaySettings['btn_icon']);

        $has_label = false;
        if (!empty($search_fields)) {
            if ($fields_to_show = $this->_fieldsSpecified($search_fields, $displaySettings['fields'])) {
                $fields_specified = true;
            } else {
                $fields_to_show = array_keys($search_fields);
                $fields_specified = false;
            }
            unset($displaySettings['fields']);
            ++$weight;
            foreach ($fields_to_show as $field_name) {
                if ((!$fields_specified && !empty($search_fields[$field_name]['disabled']))
                    || (!$field = $application->Search_Fields_impl($field_name, true))
                    || !$field->searchFieldSupports($bundle)
                ) continue;

                $field_settings = isset($search_fields[$field_name]['settings']) ? $search_fields[$field_name]['settings'] : [];
                $field_settings += $field->searchFieldInfo('default_settings');
                // Overwrite settings if any custom setting passed
                foreach (array_keys($displaySettings) as $setting_key) {
                    if (strpos($setting_key, $field_name . '_') === 0) {
                        $field_settings[substr($setting_key, strlen($field_name . '_'))] = $displaySettings[$setting_key];
                    }
                }

                // Translate form label and placeholder texts if any
                if (isset($field_settings['form']['label'])) {
                    $field_settings['form']['label'] = $application->getPlatform()->translateString(
                        $field_settings['form']['label'],
                        $bundle->name . '_' . $field_name . '_field_label',
                        'search'
                    );
                }
                if (isset($field_settings['form']['placeholder'])) {
                    $field_settings['form']['placeholder'] = $application->getPlatform()->translateString(
                        $field_settings['form']['placeholder'],
                        $bundle->name . '_' . $field_name . '_field_placeholder',
                        'search'
                    );
                }
                $field_settings['form']['btn_color'] = $btn_color;
                $_field_name = SearchComponent::FORM_PARAM_PREFIX . $field_name;
                if ($search_field_form = $field->searchFieldForm(
                    $bundle,
                    $field_settings,
                    isset($values[$_field_name]) ? $values[$_field_name] : null,
                    $values,
                    array($_field_name)
                )) {
                    $class = 'drts-search-form-field drts-search-form-field-' . str_replace('_', '-', $field_name);
                    $form[$_field_name] = $search_field_form;
                    $form[$_field_name]['#prefix'] = '<div class="' . $class . ' drts-col-md">';
                    $form[$_field_name]['#suffix'] = '</div>';
                    $form[$_field_name]['#weight'] = $fields_specified ? ++$weight : $weight + $field_settings['form']['order'];
                    $form[$_field_name]['#tree'] = true;
                    $form['#field_names'][] = $_field_name;
                    if (isset($search_field_form['#title'])
                        && strlen($search_field_form['#title'])
                    ) {
                        $has_label = true;
                    }
                }
            }
        }
        $weight += 10;
        if ($has_label) {
            $no_label_class = 'drts-search-form-field-no-label';
            foreach ($form['#field_names'] as $field_name) {
                // Add a class to field without a label
                if (!isset($form[$field_name]['#title'])
                    || !strlen($form[$field_name]['#title'])
                ) {
                    $form[$field_name]['#title'] = '&nbsp;';
                    if (!isset($form[$field_name]['#class'])) {
                        $form[$field_name]['#class'] .= ' ' . $no_label_class;
                    } else {
                        $form[$field_name]['#class'] = $no_label_class;
                    }
                }
            }
            $buttons_prefix = '<label class="' . DRTS_BS_PREFIX . 'mt-3 ' . DRTS_BS_PREFIX . 'mt-md-0">&nbsp;</label>';
        } else {
            $buttons_prefix = '';
        }

        $form['separator'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="drts-search-form-field drts-col-md-2">'
                . $buttons_prefix . '<div class="' . DRTS_BS_PREFIX . 'btn-group drts-search-form-buttons">',
            '#weight' => ++$weight,
        );

        // Load assets
        $application->getPlatform()
            ->addCssFile('search-form.min.css', 'drts-search-form', 'drts', 'directories')
            ->addJsFile('search-form.min.js', 'drts-search-form', 'drts', 'directories');

        return $form;
    }

    protected function _getButtonText(Application $application, $text, $icon)
    {
        $text = $application->getPlatform()->translateString($text, 'form_submit_btn_text', 'search');
        if ($icon) {
            if (strlen($text)) {
                $text = '<i class="' . $icon . '"></i> ' . $application->H($text);
            } else {
                $text = '<i class="' . $icon . '"></i> ';
            }
        } else {
            $text = $application->H($text);
        }
        return $text;
    }

    protected function _fieldsSpecified(array $fields, $setting = null)
    {
        if (empty($setting)) return;

        if (!is_array($setting)) {
            $fields = trim($setting);
            if (!strlen($setting)
                || (!$setting = explode(',', $setting))
            ) return;

            $setting = array_map('trim', $setting);
        }

        $ret = [];
        foreach ($setting as $field_name) {
            if (isset($fields[$field_name])) {
                $ret[$field_name] = $field_name;
            }
        }
        return $ret;
    }

    public function query(Application $application, Entity\Model\Bundle $bundle, Entity\Type\Query $query, array $values, array &$sorts)
    {
        $search_fields = empty($bundle->info['search_fields']) ? [] : $bundle->info['search_fields'];
        $search_params = $fields = [];
        foreach (array_keys($search_fields) as $field_name) {
            if (empty($search_fields[$field_name]['disabled'])
                && isset($values[$param = SearchComponent::FORM_PARAM_PREFIX . $field_name])
                && ($field = $application->Search_Fields_impl($field_name, true))
            ) {
                $search_fields[$field_name]['settings'] += $field->searchFieldInfo('default_settings');
                if ($field->searchFieldIsSearchable($bundle, $search_fields[$field_name]['settings'], $values[$param], $values)) {
                    $search_params[$param] = $_REQUEST[$param] = $values[$param]; // $_REQUEST is used by widget
                    $fields[$param] = $field_name;
                }
            }
        }
        if (empty($search_params)
            || (!$form = $application->Form_Build($application->Search_Form($bundle, $search_params)))
        ) return;

        $form->search_labels = [];
        if ($form->submit($search_params, true)) { // force submit since there is no form build ID
            foreach ($fields as $param => $field_name) {
                if (!isset($form->values[$param])) { // form validation failed
                    unset($search_params[$param]);
                } else {
                    $search_impl = $application->Search_Fields_impl($field_name);
                    $search_impl->searchFieldSearch(
                        $bundle,
                        $query,
                        $search_fields[$field_name]['settings'],
                        $form->values[$param],
                        $sorts
                    );
                    $application->Action('search_query', array($bundle, $query, $search_fields[$field_name]['settings'], $form->values[$param]));
                    $form->search_labels[$field_name] = $search_impl->searchFieldLabel(
                        $bundle,
                        $search_fields[$field_name]['settings'],
                        $form->values[$param]
                    );
                }
            }
        }
        $form->search_params = $search_params;

        return $form;
    }

    public function params(Application $application, Context $context = null)
    {
        if (isset($context)) {
            if (!$has_params = $context->getRequest()->get(SearchComponent::FORM_SEARCH_PARAM_NAME)) return [];

            $has_params = trim($has_params);
            if (strlen($has_params) === 1) return $context->getRequest()->getParams();
        } else {
            if (empty($_REQUEST[SearchComponent::FORM_SEARCH_PARAM_NAME])) return [];

            $has_params = trim($_REQUEST[SearchComponent::FORM_SEARCH_PARAM_NAME]);
            if (strlen($has_params) === 1) return $_REQUEST;
        }

        return (array)$application->getPlatform()->getCache('search-form-params-' . $has_params);
    }
}
