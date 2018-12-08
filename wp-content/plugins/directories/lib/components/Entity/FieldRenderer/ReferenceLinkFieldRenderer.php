<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ReferenceLinkFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'label' => __('Link', 'directories'),
            'field_types' => ['entity_reference'],
            'default_settings' => [
                'icon' => false,
                'icon_size' => 'sm',
                'no_link' => false,
                '_separator' => ', ',],
            'inlineable' => true,
        ];
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
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
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['bundle'])
            || (!$bundle = $this->_application->Entity_Bundle($field_settings['bundle']))
        ) return;

        $entity_ids = [];
        foreach ($values as $referenced_item) {
            $entity_ids[] = $referenced_item->getId();
        }
        if (empty($entity_ids)) return;

        $entities = $this->_application->Entity_Entities($bundle->entitytype_name, $entity_ids, !empty($settings['icon']));
        $options = [
            'no_link' => !empty($settings['no_link']),
        ];
        if (!empty($settings['icon'])) {
            $options['icon'] = $bundle->info['entity_image'];
            $options['icon_size'] = $settings['icon_size'];
            $options['icon_is_image'] = true;
        }
        foreach (array_keys($entities) as $i) {
            $ret[] = $this->_application->Entity_Permalink($entities[$i], $options);
        }

        return implode($settings['_separator'], $ret);
    }
}
