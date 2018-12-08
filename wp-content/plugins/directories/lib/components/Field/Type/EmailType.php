<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Form;

class EmailType extends StringType implements IEmail, IPersonalDataIdentifier
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Email', 'directories'),
            'default_widget' => $this->_name,
            'default_renderer' => $this->_name,
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'email',
                'check_mx' => false,
            ),
            'icon' => 'far fa-envelope',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $form = parent::fieldTypeSettingsForm($fieldType, $bundle, $settings, $parents);
        $form['char_validation']['#type'] = 'hidden';
        $form['char_validation']['#value'] = 'email';
        if (Form\Field\TextField::canCheckMx()) {
            $form['check_mx'] = array(
                '#type' => 'checkbox',
                '#title' => __('Check MX record of e-mail address', 'directories'),
                '#default_value' => $settings['check_mx'],
            );
        }
        return $form;
    }

    public function fieldSchemaProperties()
    {
        return array('email');
    }

    public function fieldEmailAddress(IField $field, Entity\Type\IEntity $entity)
    {
        return $entity->getSingleFieldValue($field->getFieldName());
    }

    public function fieldPersonalDataErase(IField $field, Entity\Type\IEntity $entity)
    {
        if (!$field->isFieldRequired()
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
        ) return true; // delete

        return $this->_application->getPlatform()->anonymizeEmail($value); // anonymize
    }

    public function fieldPersonalDataQuery(Query $query, $fieldName, $email, $userId)
    {
        $query->fieldIs($fieldName, $email);
    }

    public function fieldPersonalDataAnonymize(IField $field, Entity\Type\IEntity $entity)
    {
        return $this->_application->getPlatform()->anonymizeEmail($entity->getSingleFieldValue($field->getFieldName()));
    }
}
