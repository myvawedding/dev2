<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class IdFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\IQueryable,
    Field\Type\IHumanReadable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('ID', 'directories'),
            'creatable' => false,
        );
    }

    public function fieldSortableOptions(Field\IField $field)
    {
        return array(
            array('default' => true),
            array('args' => array('desc'), 'label' => __('%s (desc)', 'directories')),
        );
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField('id', isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }
    
    public function fieldQueryableInfo(Field\IField $field)
    {
        return array(
            'example' => '1,5,-7,12,_current_',
            'tip' => __('Enter IDs or "_current_" (for current post if any) separated with commas. Prefix with "-" to exclude, e.g. -2,-10,-_current_.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        if (!$ids = $this->_queryableParams($paramStr)) return;

        $include = $exclude = [];
        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                if (in_array($id, array('_current_', '-_current_'))
                    && isset($GLOBALS['drts_entity'])
                ) {
                    if ($id === '-_current_') {
                        $exclude[] = $GLOBALS['drts_entity']->getId();
                    } else {
                        $include[] = $GLOBALS['drts_entity']->getId();
                    }
                }
            } else {
                if ($id < 0) {
                    $exclude[] = -1 * $id;
                } else {
                    $include[] = $id;
                }
            }
        }
        if (!empty($include)) {
            $query->fieldIsIn($fieldName, $include);
        }
        if (!empty($exclude)) {
            $query->fieldIsNotIn($fieldName, $exclude);
        }
    }
    
    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        return $entity->getId();
    }
}