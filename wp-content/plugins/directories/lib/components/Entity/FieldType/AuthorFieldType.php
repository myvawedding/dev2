<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class AuthorFieldType extends Field\Type\AbstractType implements
    Field\Type\ISchemable,
    Field\Type\IQueryable,
    Field\Type\IHumanReadable,
    IPersonalDataAuthor
{
    use Field\Type\QueryableUserTrait;

    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Author', 'directories'),
            'creatable' => false,
            'icon' => 'fas fa-user',
            'admin_only' => true,
        );
    }

    public function fieldSchemaProperties()
    {
        return array('author');
    }

    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return array(array(
            '@type' => 'Person',
            'name' => $this->_application->Entity_Author($entity)->name,
        ));
    }

    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        return $this->_application->Entity_Author($entity)->name;
    }

    public function fieldPersonalDataQuery(Field\Query $query, $fieldName, $email, $userId)
    {
        $query->fieldIs($fieldName, $userId);
    }

    public function fieldPersonalDataAnonymize(Field\IField $field, Entity\Type\IEntity $entity)
    {
        return 0;
    }
}
