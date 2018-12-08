<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;

class TermContentCountFieldType extends Field\Type\AbstractType implements
    Field\Type\IHumanReadable,
    Field\Type\IQueryable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => 'Term content count',
            'entity_types' => array('term'),
            'creatable' => false,
        );
    }
    
    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'value',
                    'default' => 0,
                ),
                'content_bundle_name' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'length' => 40,
                    'was' => 'content_bundle_name',
                    'default' => '',
                ),
                'merged' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'merged',
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
                'merged' => array(
                    'fields' => array('merged' => array('sorting' => 'ascending')),
                    'was' => 'merged',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values)
    {
        $ret = [];
        foreach ($values as $value) {
            if (!is_array($value)) {
                $ret[] = false; // delete
            } else {
                $ret[] = $value;
            }
        }
        return $ret;
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity)
    {
        $all = 0;
        foreach ($values as $value) {
            // Index by child bundle name for easier access to counts
            $values[0][$value['content_bundle_name']] = (int)$value['value'];
            $values[0]['_' . $value['content_bundle_name']] = (int)$value['merged'];
            $all += $value['merged'];
            unset($values[0]['value'], $values[0]['content_bundle_name'], $values[0]['merged']);
        }
        $values[0]['_all'] = $all;
    }
    
    public function fieldTypeIsModified($field, $valueToSave, $currentLoadedValue)
    {
        $current = $new = [];
        if (!empty($currentLoadedValue[0])) {
            foreach ($currentLoadedValue[0] as $content_bundle_name => $value) {
                if (strpos($content_bundle_name, '_') === 0) continue;
                
                $current[] = array(
                    'value' => $value,
                    'content_bundle_name' => $content_bundle_name,
                    'merged' => $currentLoadedValue[0]['_' . $content_bundle_name]
                );
            }
        }
        foreach ($valueToSave as $value) {
            $new[] = array(
                'value' => (int)$value['value'],
                'content_bundle_name' => $value['content_bundle_name'],
                'merged' => (int)$value['merged']
            );
        }
        return $current !== $new;
    }
    
    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$content_bundle_types = $this->_application->Entity_TaxonomyContentBundleTypes($field->Bundle->type)) return '';
        
        $content_bundle_type = array_shift($content_bundle_types);
        if ((!$count = (int)$entity->getSingleFieldValue('entity_term_content_count', '_' . $content_bundle_type))
            || (!$bundle = $field->Bundle)
            || (!$content_bundle = $this->_application->Entity_Bundle($content_bundle_type, $bundle->component, $bundle->group))
        ) return '';
        
        return sprintf(_n($content_bundle->getLabel('count'), $content_bundle->getLabel('count2'), $count), $count);
    }
    
    public function fieldQueryableInfo(Field\IField $field)
    {
        return array(
            'example' => '5',
            'tip' => __('Enter the minimum number of content items for each taxonomy term.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        $params = $this->_queryableParams($paramStr);
        if (empty($params[0])) return;
        
        if (empty($params[1])) {
            if (!isset($bundle)
                || (!$content_bundle_types = $this->_application->Entity_TaxonomyContentBundleTypes($bundle->type))
            ) return;
            
            $content_bundle_name = array_shift($content_bundle_types);
        } else {
            $content_bundle_name = $params[1];
        }

        $query->fieldIs($fieldName, $content_bundle_name, 'content_bundle_name')
            ->fieldIsOrGreaterThan($fieldName, $params[0]);
    }
}