<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ReferenceFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'view' => null,
            ),
            'accept_multiple' => true,
        );
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'view' => array(
                '#title' => __('Select view', 'directories'),
                '#type' => 'select',
                '#horizontal' => true,
                '#options' => $this->_getViewOptions($field),
                '#default_value' => $settings['view'],
            ),
        );
    }

    protected function _getViewOptions(Field\IField $field)
    {
        $views = [];
        $field_settings = $field->getFieldSettings();
        if (!empty($field_settings['bundle'])
            && ($bundle = $this->_application->Entity_Bundle($field_settings['bundle']))
        ) {
            foreach ($this->_application->getModel('View', 'View')->bundleName_is($bundle->name)->fetch() as $view) {
                $views[$view->name] = $view->getLabel();
            }
        }
        return $views;
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        if (empty($settings['view'])) return;

        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['bundle'])
            || (!$bundle = $this->_application->Entity_Bundle($field_settings['bundle']))
        ) return;

        $referenced_item_ids = [];
        foreach ($values as $referenced_item) {
            $referenced_item_ids[] = $referenced_item->getId();
        }
        if (empty($referenced_item_ids)) return;

        return $this->_application->getPlatform()->render(
            $bundle->getPath(),
            [
                'settings' => [
                    'view' => $settings['view'],
                    'push_state' => false,
                    'show_filters' => false,
                    'settings' => [
                        'query' => [
                            'fields' => [
                                $entity->getType() . '_id' => implode(',', $referenced_item_ids),
                            ],
                        ],
                    ],
                ],
            ],
            false, // cache
            false, // title
            null, // container
            false // renderAssets
        );
    }

    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        return [
            'view' => [
                'label' => __('Select view', 'directories'),
                'value' => $this->_getViewOptions($field)[$settings['view']],
            ],
        ];
    }
}
