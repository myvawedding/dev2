<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermsFieldRenderer extends Field\Renderer\AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'icon' => false,
                'icon_size' => 'sm',
                'no_link' => false,
                '_separator' => ', ',
            ),
            'inlineable' => true,
        );
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        if (!$taxonomy_bundle = $field->getTaxonomyBundle()) return;
        
        return [
            'icon' => [
                '#type' => 'checkbox',
                '#title' => __('Show icon', 'directories'),
                '#default_value' => !empty($settings['icon']),
                '#horizontal' => true,
            ],
            'icon_size' => [
                '#type' => 'select',
                '#title' => __('Icon size', 'directories'),
                '#default_value' => isset($settings['icon_size']) ? $settings['icon_size'] : null,
                '#options' => $this->_application->System_Util_iconSizeOptions(),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['icon']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'no_link' => [
                '#type' => 'checkbox',
                '#title' => __('Do not link', 'directories'),
                '#default_value' => !empty($settings['no_link']),
                '#horizontal' => true,
            ],
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        $options = ['no_link' => !empty($settings['no_link'])];
        if (!empty($settings['icon'])) {
            $options['icon_size'] = $settings['icon_size'];
            foreach (array_keys($values) as $i) {
                if (!is_object($values[$i])) continue;

                $options['icon'] = true;
                if ($image_src = $values[$i]->getCustomProperty('image_src')) {
                    $options['icon'] = $image_src;
                    $options['icon_is_value'] = $options['icon_is_image'] = true;
                } elseif ($icon_src = $values[$i]->getCustomProperty('icon_src')) {
                    $options['icon'] = $icon_src;
                    $options['icon_is_value'] = $options['icon_is_image'] = $options['icon_is_full'] = true;
                } elseif ($icon = $values[$i]->getCustomProperty('icon')) {
                    $options['icon'] = $icon;
                    $options['icon_is_value'] = true;
                    $options['icon_color'] = $values[$i]->getCustomProperty('color');
                }
                $ret[] = $this->_application->Entity_Permalink($values[$i], $options);
            }
        } else {
            foreach (array_keys($values) as $i) {
                if (!is_object($values[$i])) continue;

                $ret[] = $this->_application->Entity_Permalink($values[$i], $options);
            }
        }
        
        return implode($settings['_separator'], $ret);
    }
    
    public function fieldRendererSupportsAmp(Entity\Model\Bundle $bundle)
    {
        return true;
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $ret = [
            'icon' => [
                'label' => __('Show icon', 'directories'),
                'value' => !empty($settings['icon']),
                'is_bool' => true,
            ],
        ];
        return $ret;
    }
    
    public function fieldRendererIsPreRenderable(Field\IField $field, array $settings)
    {
        return false;
        // Require pre-rendering if icon or icon colfor needs to be fetched from a field
        
        if (empty($settings['icon'])) return false;
        
        if (isset($settings['icon_settings']['field']) && $settings['icon_settings']['field'] !== '') return true;
        
        if (isset($settings['icon_settings']['color']['type'])
            && $settings['icon_settings']['color']['type'] !== ''
            && $settings['icon_settings']['color']['type'] !== 'custom'
        ) return true;
        
        return false;
    }
    
    public function fieldRendererPreRender(Field\IField $field, array $settings, array $entities)
    {
        $terms = [];
        foreach (array_keys($entities) as $entity_id) {
            foreach ($entities[$entity_id]->getFieldValue($field->getFieldName()) as $term) {
                $terms[$term->getId()] = $term;
            }
        }
        if (!empty($terms)) {
            $this->_application->Entity_LoadFields($term->getType(), $terms);
        }
    }
}