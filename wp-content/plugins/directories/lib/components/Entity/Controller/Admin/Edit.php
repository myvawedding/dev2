<?php
namespace SabaiApps\Directories\Component\Entity\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Form;

class Edit extends System\Controller\Admin\AbstractSettings
{    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        if ($context->getRequest()->asBool('show_settings')) {
            return [
                'settings' => [
                    '#type' => 'markup',
                    '#markup' => '<pre>' . var_export($context->bundle->info, true) . '</pre>',
                ],
            ];
        }
        
        // Add label settings
        $info = $this->Entity_BundleTypeInfo($context->bundle);
        $form = [
            '#tabs' => [
                'general' => [
                    '#title' => __('General', 'directories'),
                    '#weight' => -1,
                ],
            ],
            '#tab_style' => 'pill_less_margin',
            'general' => [
                '#tab' => 'general',
                '#tree' => false,
                'labels' => [
                    '#title' => __('Label Settings', 'directories'),
                    '#weight' => 1,
                    'label' => [
                        '#type' => 'textfield',
                        '#title' => __('Label', 'directories'),
                        '#default_value' => $context->bundle->getLabel(),
                        '#horizontal' => true,
                        '#placeholder' => isset($info['label']) ? $info['label'] : null,
                        '#required' => true,
                        '#weight' => -2,
                    ],
                    'label_singular' => [
                        '#type' => 'textfield',
                        '#title' => __('Singular label', 'directories'),
                        '#default_value' => $context->bundle->getLabel('singular'),
                        '#horizontal' => true,
                        '#placeholder' => isset($info['label_singular']) ? $info['label_singular'] : null,
                        '#required' => true,
                        '#weight' => -1,
                    ],
                ],
            ],
        ];
        $labels = [
            'add' => __('Add item label', 'directories'),
            'all' => __('All items label', 'directories'),
            'select' => __('Select item label', 'directories'),
            'count' => __('Item count label', 'directories'),
            'count2' => __('Item count label (plural)', 'directories'),
        ];
        foreach ($labels as $label_name => $label_title) {
            $label_setting_name = 'label_' . $label_name;
            if (isset($info[$label_setting_name])) {
                $form['general']['labels'][$label_setting_name] = [
                    '#type' => 'textfield',
                    '#title' => $label_title,
                    '#default_value' => $context->bundle->getLabel($label_name),
                    '#horizontal' => true,
                    '#placeholder' => is_string($info[$label_setting_name]) ? $info[$label_setting_name] : null,
                    '#required' => true,
                ];
            }
        }
        if (!empty($info['public'])) {
            if (!empty($info['is_taxonomy'])
                || !empty($info['parent'])
            ) {
                $form['general']['labels'] += [
                    'label_page' => [
                        '#type' => 'textfield',
                        '#title' => __('Single item page label', 'directories'),
                        '#default_value' => $context->bundle->getLabel('page'),
                        '#horizontal' => true,
                        '#placeholder' => is_string($info['label_page']) ? $info['label_page'] : null,
                        '#required' => true,
                    ],
                ];
            }
        }

        if (!empty($info['entity_image'])
            || !empty($info['entity_icon'])
        ) {
            $form['general']['image'] = [
                '#title' => __('Image Settings', 'directories'),
                '#weight' => 40,
                '#tree' => true,
            ];
            if (!empty($info['entity_image'])) {
                if ($image_fields = $this->Entity_Field_options($context->bundle, ['interface' => 'Field\Type\IImage'])) {
                    $form['general']['image']['entity_image'] = [
                        '#type' => 'select',
                        '#title' => __('Default image field', 'directories'),
                        '#options' => $image_fields,
                        '#default_value' => !empty($context->bundle->info['entity_image']) ? $context->bundle->info['entity_image'] : null,
                        '#horizontal' => true,
                    ];
                }
            }
            if (!empty($info['entity_icon'])) {
                $icon_fields = $this->Entity_Field_options($context->bundle, ['interface' => 'Field\Type\IconType']);
                if (!isset($image_fields)) {
                    $image_fields = $this->Entity_Field_options($context->bundle, ['interface' => 'Field\Type\IImage']);
                }
                if ($image_fields) $icon_fields += $image_fields;
                if ($icon_fields) {
                    $form['general']['image']['entity_icon'] = [
                        '#type' => 'select',
                        '#title' => __('Default icon field', 'directories'),
                        '#options' => $icon_fields,
                        '#default_value' => !empty($context->bundle->info['entity_icon']) ? $context->bundle->info['entity_icon'] : null,
                        '#horizontal' => true,
                    ];
                }
            }
        }
        
        if (empty($info['is_taxonomy'])
            && !empty($info['public'])
            && empty($info['internal'])
        ) {
            $form['general']['seo'] = array(
                '#title' => __('SEO Settings', 'directories'),
                '#weight' => 50,
                'entity_schemaorg' => array('#tree' => true) + $this->Entity_SchemaOrg_settingsForm(
                    $context->bundle,
                    empty($context->bundle->info['entity_schemaorg']) ? [] : $context->bundle->info['entity_schemaorg'],
                    array('entity_schemaorg')
                ),
                'entity_opengraph' => array('#tree' => true) + $this->Entity_OpenGraph_settingsForm(
                    $context->bundle,
                    empty($context->bundle->info['entity_opengraph']) ? [] : $context->bundle->info['entity_opengraph'],
                    array('entity_opengraph')
                ),
            );
        }
        
        $submitted_values = $this->_getSubimttedValues($context, $formStorage);
        
        // Add bundle type specific settings
        $form[$context->bundle->type] = array('#tree' => false);
        $form[$context->bundle->type] += (array)$this->Entity_BundleTypes_impl($context->bundle->type)
            ->entityBundleTypeSettingsForm($context->bundle->info, [], $submitted_values);
        
        $form = $this->Filter('entity_bundle_settings_form', $form, array($context->bundle, $submitted_values));
        if (count($form['#tabs']) <= 1) $form['#tabs'] = [];
        
        return $form;
    }
    
    protected function _saveConfig(Context $context, array $values, Form\Form $form)
    {
        parent::_saveConfig($context, $values, $form);

        $values['entity_image'] = $values['entity_icon'] = '';
        if (!empty($values['image']['entity_image'])) {
            $values['entity_image'] = $values['image']['entity_image'];
        }
        if (!empty($values['image']['entity_icon'])) {
            $values['entity_icon'] = $values['image']['entity_icon'];
            if ($icon_field = $this->_application->Entity_Field($context->bundle, $values['entity_icon'])) {
                $values['entity_icon_is_image'] = $icon_field->getFieldType() !== 'icon';
            } else {
                unset($values['entity_icon']);
            }
        }
        unset($values['image']);

        // Clear taxonomy cache if image or icon field changed
        if (!empty($context->bundle->info['is_taxonomy'])) {
            if ($context->bundle->info['entity_image'] !== $values['entity_image']
                || $context->bundle->info['entity_icon'] !== $values['entity_icon']
            ) {
                $clear_taxonomy_cache = true;
            }
        }
        
        $context->bundle->setInfo($values)->commit();
        
        $this->Action('entity_admin_bundle_info_edited', array($context->bundle));

        if ($clear_taxonomy_cache) {
            $this->Entity_TaxonomyTerms_clearCache($context->bundle->name);
            $this->Entity_FieldCache_clean();
        }
    }
}