<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class StringType extends AbstractValueType implements
    ISortable,
    ISchemable,
    IQueryable,
    IOpenGraph,
    IHumanReadable,
    IConditionable,
    IPersonalData
{
    use QueryableStringTrait, ConditionableStringTrait;

    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Single Line Text', 'directories'),
            'default_widget' => 'textfield',
            'default_renderer' => 'string',
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'none',
                'regex' => null,
            ),
            'icon' => 'fas fa-minus',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return array(
            'min_length' => array(
                '#type' => 'number',
                '#title' => __('Minimum length', 'directories'),
                '#description' => __('The minimum length of value in characters.', 'directories'),
                '#size' => 5,
                '#integer' => true,
                '#default_value' => $settings['min_length'],
            ),
            'max_length' => array(
                '#type' => 'number',
                '#title' => __('Maximum length', 'directories'),
                '#description' => __('The maximum length of value in characters.', 'directories'),
                '#size' => 5,
                '#integer' => true,
                '#default_value' => $settings['max_length'],
            ),
            'char_validation' => array(
                '#type' => 'select',
                '#title' => __('Character validation', 'directories'),
                '#options' => array(
                    'integer' => __('Allow only integer numbers', 'directories'),
                    'alpha' => __('Allow only alphabetic characters', 'directories'),
                    'alnum' => __('Allow only alphanumeric characters', 'directories'),
                    'lower' => __('Allow only lowercase characters', 'directories'),
                    'upper' => __('Allow only uppercase characters', 'directories'),
                    'url' => __('Must be a valid URL', 'directories'),
                    'email' => __('Must be a valid e-mail address', 'directories'),
                    'regex' => __('Must match a regular expression', 'directories'),
                    'none' => __('No validation', 'directories'),
                ),
                '#default_value' => $settings['char_validation'],
            ),
            'regex' => array(
                '#type' => 'textfield',
                '#title' => __('Regular Expression', 'directories'),
                '#description' => __('Example: /^[0-9a-z]+$/i', 'directories'),
                '#default_value' => $settings['regex'],
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[char_validation]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'regex'),
                    ),
                ),
                '#required' => array(array($this, 'isRegexRequired'), array($parents)),
                '#display_unrequired' => true,
                '#size' => 20,
            ),
        );
    }

    public function isRegexRequired($form, $parents)
    {
        $values = $form->getValue($parents);
        return @$values['char_validation'] === 'regex';
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending', 'length' => 191)),
                    'was' => 'value',
                ),
            ),
        );
    }

    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('desc'), 'label' => sprintf(__('%s (desc)', 'directories'), $field))
        );
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }

    public function fieldSchemaProperties()
    {
        return array('name', 'alternateName');
    }

    public function fieldSchemaRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        return $entity->getFieldValue($field->getFieldName());
    }

    public function fieldOpenGraphProperties()
    {
        return array('books:isbn', 'music:isrc', 'product:isbn');
    }

    public function fieldOpenGraphRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return array($value);
    }

    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';

        return implode(isset($separator) ? $separator : ', ', $values);
    }
    
    public function fieldPersonalDataExport(IField $field, Entity\Type\IEntity $entity)
    {
        return implode(', ', $entity->getFieldValue($field->getFieldName()));
    }

    public function fieldPersonalDataErase(IField $field, Entity\Type\IEntity $entity)
    {
        if (!$field->isFieldRequired()
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
        ) return true; // delete

        return $this->_application->getPlatform()->anonymizeText($value); // anonymize
    }
}
