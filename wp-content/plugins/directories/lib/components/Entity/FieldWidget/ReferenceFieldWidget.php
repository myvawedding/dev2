<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ReferenceFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Autocomplete text field', 'directories'),
            'field_types' => array($this->_name),
            'accept_multiple' => false,
            'repeatable' => true,
            'default_settings' => [
                'own_only' => false,
            ],
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'own_only' => [
                '#title' => __('Auto-suggest own content only', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['own_only']),
            ],
        ];
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$bundle = $this->_getReferenceBundle($field)) return;

        return array(
            '#type' => 'autocomplete',
            '#default_value' => isset($value) ? (is_object($value) ? $value->getId() : $value) : null,
            '#select2' => true,
            '#select2_ajax' => true,
            '#select2_ajax_url' => $this->_getAjaxUrl($bundle, $settings, $language),
            '#select2_item_text_key' => 'title',
            '#default_options_callback' => array(array($this, '_getDefaultOptions'), array($bundle->entitytype_name)),
        );
    }

    public function _getDefaultOptions($defaultValue, array &$options, $entityType)
    {
        foreach ($this->_application->Entity_Types_impl($entityType)->entityTypeEntitiesByIds($defaultValue) as $entity) {
            $options[$entity->getId()] = $this->_application->Entity_Title($entity);
        }
    }

    private function _getReferenceBundle(Field\IField $field)
    {
        $settings = $field->getFieldSettings();
        if (empty($settings['bundle'])) return;

        return $this->_application->Entity_Bundle($settings['bundle']);
    }

    protected function _getAjaxUrl(Entity\Model\Bundle $bundle, array $settings, $language = null)
    {
        $params = [
            'bundle' => $bundle->name,
            Request::PARAM_CONTENT_TYPE => 'json',
            'language' => $language,
        ];
        if (!empty($settings['own_only'])
            && !$this->_application->getUser()->isAnonymous()
        ) {
            $params['user_id'] = $this->_application->getUser()->id;
        }
        return $this->_application->MainUrl(
            '/_drts/entity/' . $bundle->type . '/query',
            $params,
            '',
            '&'
        );
    }
}
