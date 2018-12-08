<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class UrlType extends StringType
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('URL', 'directories'),
            'default_widget' => $this->_name,
            'default_renderer' => $this->_name,
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'url',
            ),
            'icon' => 'fas fa-link',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $form = parent::fieldTypeSettingsForm($fieldType, $bundle, $settings, $parents);
        $form['char_validation']['#type'] = 'hidden';
        $form['char_validation']['#value'] = 'url';
        return $form;
    }

    public function fieldSchemaProperties()
    {
        return array('url');
    }

    public function fieldOpenGraphProperties()
    {
        return array('og:audio', 'og:video', 'books:sample', 'product:product_link');
    }

    public function fieldOpenGraphRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$url = $entity->getSingleFieldValue($field->getFieldName())) return;

        return array($url);
    }

    public function fieldPersonalDataErase(IField $field, Entity\Type\IEntity $entity)
    {
        if (!$field->isFieldRequired()
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
        ) return true; // delete

        return $this->_application->getPlatform()->anonymizeUrl($value); // anonymize
    }
}
