<?php
namespace SabaiApps\Directories\Component\Entity\Type;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Framework\Paginator\CustomPaginator;

class Query
{
    protected $_application, $_entityType, $_fieldQuery, $_bundleNames, $_bundlesQueried = false, $_randomSortSeedLifetime;
    
    public function __construct(Application $application, $entityType, FieldQuery $fieldQuery)
    {
        $this->_application = $application;
        $this->_entityType = $entityType;
        $this->_fieldQuery = $fieldQuery;
    }
    
    public function getEntityType()
    {
        return $this->_entityType;
    }
    
    /**
     * @return FieldQuery
     */
    public function getFieldQuery()
    {
        return $this->_fieldQuery;
    }
    
    public function __call($name, $args)
    {
        call_user_func_array(array($this->_fieldQuery, $name), $args);
        
        return $this;
    }
    
    public function fetch($limit = 0, $offset = 0, $loadEntityFields = true, $saveEntityIds = false)
    {
        $entities = $this->_application->Entity_Storage()->query($this->_entityType, $this->_fieldQuery, $limit, $offset, $saveEntityIds);
        if (empty($entities)) return [];
        
        $_entities = $this->_application->Entity_Types_impl($this->_entityType)->entityTypeEntitiesByIds(array_keys($entities));
        if ($loadEntityFields) {
            $force = false;
            $cache = true;
            if (is_array($loadEntityFields)) {
                if (isset($loadEntityFields['force'])) {
                    $force = !empty($loadEntityFields['force']);
                }
                if (isset($loadEntityFields['cache'])) {
                    $cache = !empty($loadEntityFields['cache']);
                }
            }
            $this->_application->Entity_LoadFields($this->_entityType, $_entities, $force, $cache);
        }

        foreach (array_keys($entities) as $entity_id) {
            if (!isset($_entities[$entity_id])) {
                unset($entities[$entity_id]);
                continue;
            }
            if (is_array($entities[$entity_id])) {
                // Set extra fields queried as entity data
                foreach ($entities[$entity_id] as $field_name => $data) {
                    $field_value = $_entities[$entity_id]->getFieldValue($field_name);
                    $new_field_value = [];
                    foreach ($data as $weight => $_data) {
                        if (isset($field_value[$weight])) {
                            $new_field_value[] = $_data + $field_value[$weight];
                        }
                    }
                    $_entities[$entity_id]->setFieldValue($field_name, $new_field_value);
                }
            }
            $entities[$entity_id] = $_entities[$entity_id];
        }

        return array_intersect_key($entities, $_entities);
    }
    
    public function count($limit = 0, $offset = 0)
    {
        return $this->_application->Entity_Storage()->queryCount($this->_entityType, $this->_fieldQuery, $limit, $offset);
    }

    public function paginate($perpage = 20, $limit = 0, $loadEntityFields = true, $saveEntityIds = false)
    {
        $paginator = new CustomPaginator(
            array($this, 'count'),
            array($this, 'fetch'),
            $perpage,
            array($loadEntityFields, false),
            [],
            [],
            $limit
        );
        if ($saveEntityIds) {
            if ($paginator->count() > 1) {
                // Need to save entity IDs because fetch() method will not get all entity IDs
                $this->_application->Entity_Storage()->query($this->_entityType, $this->_fieldQuery, 0, 0, true);
            } else {
                // Let fetch() method save found entity IDs
                $paginator->setExtraParams(array($loadEntityFields, true));
            }
        }
        
        return $paginator;
    }
    
    public function facets($fieldName, $valueColumn = 'value', Field\Query $fieldQuery = null, array $otherColumns = null)
    {
        $field_query = isset($fieldQuery) ? $fieldQuery : $this->_fieldQuery;
        return $this->_application->Entity_Storage()->queryValueCount($this->_entityType, $field_query, $fieldName, $valueColumn, $otherColumns);
    }
    
    public function sort($sort, array $sorts = null, $randomSortSeedName = 'default')
    {
        if ($sort === 'random') {
            $this->_fieldQuery->sortByRandom($this->_getRandomSortSeed($randomSortSeedName));
        } elseif (isset($sort)) {
            if (isset($sorts[$sort])) {
                $_sort = $sorts[$sort];
                if (isset($_sort['field_type'])
                    && ($field_type = $this->_application->Field_Type($_sort['field_type'], true))
                    && $field_type instanceof \SabaiApps\Directories\Component\Field\Type\ISortable
                ) {
                    if (strpos($sort, ',')) {
                        $args = explode(',', $sort);
                        array_shift($args); // remove field name part
                        if (isset($_sort['args'])) {
                            $args += $_sort['args'];
                        }
                    } else {
                        $args = isset($_sort['args']) ? $_sort['args'] : null;
                    }
                    $field_type->fieldSortableSort($this->_fieldQuery, isset($_sort['field_name']) ? $_sort['field_name'] : $_sort['field_type'], $args);
                }
            } else {
                $this->_fieldQuery->sortById();
            }
        } else {
            $this->_fieldQuery->sortById();
        }
        
        return $this;
    }
    
    protected function _getRandomSortSeed($name)
    {
        if (!isset($this->_randomSortSeedLifetime)) {
            $this->_randomSortSeedLifetime = $this->_application->Filter('entity_query_random_sort_seed_lifetime', 3600); // defaults to 1 hour
        }
        $cache_id = 'entity_query_random_sort_seed__' . $name;
        if (!$random_seed = $this->_application->getPlatform()->getCache($cache_id)) {
            $random_seed = mt_rand(1, 9999);
            $this->_application->getPlatform()->setCache($random_seed, $cache_id, $this->_randomSortSeedLifetime);
        }
        
        return $random_seed;
    }
}