<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Form\Form;

class TitleFieldRenderer extends Field\Renderer\AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'link' => 'post',
                'link_field' => null,
                'link_target' => '_self',
                'link_rel' => null,
                'link_custom_label' => null,
                'max_chars' => 0,
                'show_count' => false,
                'content_bundle_type' => null,
            ),
            'inlineable' => true,
        );
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {        
        $form = array(
            'link' => array(
                '#type' => 'select',
                '#title' => __('Link type', 'directories'),
                '#options' => $this->_getTitleLinkTypeOptions(),
                '#default_value' => $settings['link'],
                '#weight' => 1,
            ),
            'link_target' => array(
                '#title' => __('Open link in', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkTargetOptions(),
                '#default_value' => $settings['link_target'],
                '#weight' => 4,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'value', 'value' => ''), 
                    ),
                ),
            ),
            'link_custom_label' => array(
                '#title' => __('Custom link label', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['link_custom_label'],
                '#weight' => 5,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'value', 'value' => ''),
                    ),
                ),
            ),
        );
        
        // Allow linking title to another field if any linkable field exists
        $bundle = $this->_application->Entity_Bundle($field->bundle_name);
        if ($bundle_fields = $this->_application->Entity_Field($bundle->name)) {
            $linkable_fields = $this->_application->Filter('entity_linkable_fields', array(
                'url' => '',
            ), array($bundle));
            if (!empty($linkable_fields)) {
                $link_field_options = [];
                foreach ($bundle_fields as $bundle_field_name => $bundle_field) {
                    $bundle_field_type = $bundle_field->getFieldType();
                    if (isset($linkable_fields[$bundle_field_type])) {
                        $link_field_options[$bundle_field_name . ',' . $linkable_fields[$bundle_field_type]] = $bundle_field->getFieldLabel() . ' - ' . $bundle_field_name;
                    }
                }
                if (!empty($link_field_options)) {
                    asort($link_field_options);
                    $form['link']['#options'] += $this->_getTitleLinkTypeExtraOptions();
                    $form['link_field'] = array(
                        '#type' => 'select',
                        '#default_value' => $settings['link_field'],
                        '#options' => $link_field_options,
                        '#weight' => 2,
                        '#required' => function($form) use ($parents) { return in_array($form->getValue(array_merge($parents, array('link'))), array('field', 'field_post')); },
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['field', 'field_post']), 
                            ),
                        ),
                    );
                    $form['link_rel'] = array(
                        '#title' => __('Add to "rel" attribute', 'directories'),
                        '#weight' => 3,
                        '#inline' => true,
                        '#type' => 'checkboxes',
                        '#options' => $this->_getLinkRelAttrOptions(),
                        '#default_value' => $settings['link_rel'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'one', 'value' => ['field', 'field_post']), 
                            ),
                        ),
                    );
                }
            }
        }
        
        $form['max_chars'] = [
            '#title' => __('Max number of characters', 'directories'),
            '#type' => 'slider',
            '#integer' => true,
            '#min_value' => 0,
            '#max_value' => 500,
            '#min_text' => __('Unlimited', 'directories'),
            '#step' => 10,
            '#weight' => 10,
            '#default_value' => $settings['max_chars'],
        ];
        
        $form += $this->_application->System_Util_iconSettingsForm($bundle, $settings, $parents, 10);
        
        if (!empty($bundle->info['is_taxonomy'])
            && ($taxonomy_content_bundle_types = $this->_application->Entity_TaxonomyContentBundleTypes($bundle->type))
        ) {
            $form['show_count'] = array(
                '#type' => 'checkbox',
                '#title' => __('Show post count', 'directories'),
                '#default_value' => !empty($settings['show_count']),
                '#weight' => 15,
            );
            if (count($taxonomy_content_bundle_types) > 1) {
                $options = [];
                foreach ($taxonomy_content_bundle_types as $content_bundle_type) {
                    $options[$content_bundle_type] = $this->_application->Entity_Bundle($content_bundle_type, $bundle->component, $bundle->group)->getLabel('singular');
                }
                $form['content_bundle_type'] = array(
                    '#type' => 'select',
                    '#options' => $options,
                    '#default_value' => $settings['content_bundle_type'],
                    '#states' => array(
                        'visible' => array(
                            sprintf('input[name="%s[show_count]"]', $this->_application->Form_FieldName($parents)) => array(
                                'type' => 'checked', 
                                'value' => true,
                            ),
                        ),
                    ),
                    '#weight' => 16,
                );
            } else {
                $form['content_bundle_type'] = array(
                    '#type' => 'hidden',
                    '#value' => current($taxonomy_content_bundle_types),
                );
            }
            $form['show_count_label'] = array(
                '#type' => 'checkbox',
                '#title' => __('Show post count with label', 'directories'),
                '#default_value' => !empty($settings['show_count_label']),
                '#weight' => 17,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[show_count]"]', $this->_application->Form_FieldName($parents)) => array(
                            'type' => 'checked', 
                            'value' => true,
                        ),
                    ),
                ),
            );
        }
        
        return $form;
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $options = array('atts' => array('target' => $settings['link_target']));
        if (!empty($settings['link'])
            && $entity->isPublished()
        ) {
            if ($settings['link'] === 'field' || $settings['link'] === 'field_post') {
                if (!empty($settings['link_field'])
                    && ($link_field = explode(',', $settings['link_field']))
                    && ($url = $entity->getSingleFieldValue($link_field[0], strlen($link_field[1]) ? $link_field[1] : null))
                ) {
                    $options['script_url'] = $url;
                    if (!empty($settings['link_rel'])) {
                        $options['atts']['rel'] = implode(' ', $settings['link_rel']);
                    }
                } else {
                    if ($settings['link'] === 'field') {
                        $options['no_link'] = true;
                    }
                }
            }
        } else {
            $options['no_link'] = true;
        }
        
        if (!empty($settings['icon'])
            && ($bundle = $this->_application->Entity_Bundle($entity))
        ) {
            $options += $this->_application->System_Util_iconSettingsToPermalinkOptions($bundle, $settings['icon_settings']);
        }

        if (empty($options['no_link'])) {
            $options['title'] = isset($settings['link_custom_label']) && strlen($settings['link_custom_label']) ? $settings['link_custom_label'] : $this->_application->Entity_Title($entity);
        } else {
            $options['title'] = $this->_application->Entity_Title($entity);
        }
        
        // Limit number of chars?
        if (!empty($settings['max_chars'])) {
            $options['title'] = $this->_application->Summarize($options['title'], $settings['max_chars']);
        }
        
        $title = $this->_application->Entity_Permalink($entity, $options);
        
        if (!empty($settings['show_count'])
            && !empty($settings['content_bundle_type'])
            && ($count = (int)$entity->getSingleFieldValue('entity_term_content_count', '_' . $settings['content_bundle_type']))
        ) {
            if (!empty($settings['show_count_label'])
                && ($bundle = $field->Bundle)
                && ($content_bundle = $this->_application->Entity_Bundle($settings['content_bundle_type'], $bundle->component, $bundle->group))
            ) {
                $count = sprintf(_n($content_bundle->getLabel('count'), $content_bundle->getLabel('count2'), $count), $count);
            } else {
                $count = '(' . $count . ')';
            }
            $title = $title . ' <span style="vertical-align:middle">' . $count . '</span>';
        }
        
        return $title; 
    }
    
    protected function _getTitleLinkTypeOptions()
    {
        return [
            'post' => __('Permalink', 'directories'),
            '' => __('Do not link', 'directories'),
        ];
    }
    
    protected function _getTitleLinkTypeExtraOptions()
    {
        return [
            'field' => __('Link to URL of another field', 'directories'),
            'field_post' => __('Link to URL of another field (fallback to post URL)', 'directories'),
        ];
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $bundle = $this->_application->Entity_Bundle($field->bundle_name);
        if (in_array($settings['link'], ['field', 'field_post'])) {
            $link_options = $this->_getTitleLinkTypeExtraOptions();
            $link_value = $link_options[$settings['link']] . ' - ' . explode(',', $settings['link_field'])[0];
        } else {
            $link_options = $this->_getTitleLinkTypeOptions();
            $link_value = $link_options[$settings['link']];
        }
        $targets = $this->_getLinkTargetOptions();
        $ret = [
            'link' => [
                'label' => __('Link type', 'directories'),
                'value' => $link_value,
            ],
        ];
        if ($settings['link'] !== '') {
            if ($settings['link'] !== 'post') {
                if (!empty($settings['link_rel'])) {
                    $rels = $this->_getLinkRelAttrOptions();
                    $value = [];
                    foreach ($settings['link_rel'] as $rel) {
                        $value[] = $rels[$rel];
                    }
                    $ret['link_rel'] = [
                        'label' => __('Link "rel" attribute', 'directories'),
                        'value' => implode(', ', $value),
                    ];
                }
            }
            $ret['link_target'] = [
                'label' => __('Open link in', 'directories'),
                'value' => $targets[$settings['link_target']],
            ];
            if (isset($settings['link_custom_label'])
                && strlen($settings['link_custom_label'])
            ) {
                $ret['link_custom_label'] = [
                    'label' => __('Custom link label', 'directories'),
                    'value' => $settings['link_custom_label'],
                ];
            }
        }
        if (isset($settings['icon'])) {
            $ret['icon'] = [
                'label' => __('Show icon', 'directories'),
                'value' => !empty($settings['icon']),
                'is_bool' => true, 
            ];
        }
        if (!empty($bundle->info['is_taxonomy'])) {
            $ret['show_count'] = [
                'label' => __('Show post count', 'directories'),
                'value' => !empty($settings['show_count']),
                'is_bool' => true,
            ];
        }
        return $ret;
    }
}