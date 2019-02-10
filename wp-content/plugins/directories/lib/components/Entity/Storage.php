<?php
namespace SabaiApps\Directories\Component\Entity;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field;

class Storage
{
    private static $_instance;
    protected $_application, $_parsers = [], $_queries = [], $_fieldValueCountCacheLifetime;
    
    private function __construct(Application $application)
    {
        $this->_application = $application;
    }
    
    public static function getInstance(Application $application)
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self($application);
        }
        return self::$_instance;
    }

    public function saveValues(Type\IEntity $entity, array $fieldValues)
    {
        $db = $this->_application->getDB();
        $db->begin();
        $entity_type_escaped = $db->escapeString($entity->getType());
        $bundle_name_escaped = $db->escapeString($this->_application->Entity_Bundle($entity)->name);
        foreach ($fieldValues as $field_name => $field_values) {
            if (!$field_type = $this->getFieldSchemaType($field_name)) continue;
            
            $column_types = $this->getFieldColumnType($field_type);
            
            // Skip if no schema defined for this field
            if (empty($column_types)) continue;
            
            $field_name_escaped = $db->escapeString($field_name);

            // Delete current values of the entity
            try {
                $db->exec(sprintf(
                    'DELETE FROM %sentity_field_%s WHERE entity_type = %s AND entity_id = %d AND field_name = %s',
                    $db->getResourcePrefix(),
                    $field_type,
                    $entity_type_escaped,
                    $entity->getId(),
                    $field_name_escaped
                ));
            } catch (\Exception $e) {
                $db->rollback();
                throw $e;
            }

            // Insert values
            foreach ($field_values as $weight => $field_value) {
                if (!is_array($field_value)) continue;
                
                $values = [];
                foreach (array_intersect_key($field_value, $column_types) as $column => $value) {
                    $values[$column] = $this->escapeFieldValue($value, $column_types[$column]);
                }
                try {
                    $sql = sprintf(
                        'INSERT INTO %sentity_field_%s (entity_type, bundle_name, entity_id, field_name, weight%s) VALUES (%s, %s, %d, %s, %d%s)',
                        $db->getResourcePrefix(),
                        $field_type,
                        empty($values) ? '' : ', ' . implode(', ', array_keys($values)),
                        $entity_type_escaped,
                        $bundle_name_escaped,
                        $entity->getId(),
                        $field_name_escaped,
                        $weight,
                        empty($values) ? '' : ', ' . implode(', ', $values)
                    );
                    $db->exec($sql);
                } catch (\Exception $e) {
                    $db->rollback();
                    throw $e;
                }
            }
        }
        $db->commit();
    }

    public function fetchValues($entityType, array $entityIds, array $fields)
    {
        $values = [];
        $db = $this->_application->getDB();
        $entity_type_escaped = $db->escapeString($entityType);
        $entity_ids_escaped = implode(',', array_map('intval', $entityIds));
        foreach ($fields as $field_name) {
            if (!$field_type = $this->getFieldSchemaType($field_name)) continue;
                    
            $column_types = $this->getFieldColumnType($field_type);
            
            // Skip if no schema defined for this field
            if (empty($column_types)) continue;

            try {
                $rs = $db->query(sprintf(
                    'SELECT entity_id, %s FROM %sentity_field_%s WHERE entity_type = %s AND entity_id IN (%s) AND field_name = %s ORDER BY weight ASC',
                    implode(', ', array_keys($column_types)),
                    $db->getResourcePrefix(),
                    $field_type,
                    $entity_type_escaped,
                    $entity_ids_escaped,
                    $db->escapeString($field_name)
                ));
            } catch (\Exception $e) {
                $this->_application->logError($e);
                continue;
            }
            foreach ($rs as $row) {
                $entity_id = $row['entity_id'];
                unset($row['entity_id']);
                foreach ($column_types as $column => $column_type) {
                    switch ($column_type) {
                        case Application::COLUMN_INTEGER:
                            $row[$column] = intval($row[$column]);
                            break;
                        case Application::COLUMN_DECIMAL:
                            $row[$column] = str_replace(',', '.', floatval($row[$column]));
                            break;
                        case Application::COLUMN_BOOLEAN:
                            $row[$column] = (bool)$row[$column];
                            break;
                    }
                }
                $values[$entity_id][$field_name][] = $row;
            }
        }

        return $values;
    }

    public function purgeValues($entityType, array $entityIds, array $fields)
    {
        $db = $this->_application->getDB();
        $db->begin();
        $entity_type_escaped = $db->escapeString($entityType);
        $entity_ids_escaped = implode(',', array_map('intval', $entityIds));
        foreach ($fields as $field_name) {
            if (!$field_type = $this->getFieldSchemaType($field_name)) continue;
                    
            $column_types = $this->getFieldColumnType($field_type);
            
            // Skip if no schema defined for this field
            if (empty($column_types)) continue;

            // Delete all values of the entity
            try {
                $db->exec(sprintf(
                    'DELETE FROM %sentity_field_%s WHERE entity_type = %s AND entity_id IN (%s) AND field_name = %s',
                    $db->getResourcePrefix(),
                    $field_type,
                    $entity_type_escaped,
                    $entity_ids_escaped,
                    $db->escapeString($field_name)
                ));
            } catch (\Exception $e) {
                $db->rollback();
                $this->_application->logError($e);
            }
        }
        $db->commit();
    }
    
    public function purgeValuesByBundle(array $bundleNames, array $fields)
    {
        $db = $this->_application->getDB();
        $db->begin();
        $bundle_names_escaped = implode(',', array_map(array($db, 'escapeString'), $bundleNames));
        foreach ($fields as $field_name) {
            if (!$field_type = $this->getFieldSchemaType($field_name)) continue; 
                    
            $column_types = $this->getFieldColumnType($field_type);
            
            // Skip if no schema defined for this field
            if (empty($column_types)) continue;

            // Delete all values of the entity
            try {
                $db->exec(sprintf(
                    'DELETE FROM %sentity_field_%s WHERE bundle_name IN (%s) AND field_name = %s',
                    $db->getResourcePrefix(),
                    $field_type,
                    $bundle_names_escaped,
                    $db->escapeString($field_name)
                ));
            } catch (\Exception $e) {
                $db->rollback();
                $this->_application->logError($e);
            }
        }
        $db->commit();
    }

    public function create(array $fields)
    {
        $this->_application->getPlatform()->deleteCache('entity_storage_field_schema');
        if ($schema = $this->_getSchema($fields)) {
            $this->_application->getPlatform()->updateDatabase($schema);
        }
    }
    
    public function update(array $fields)
    {
        $this->_application->getPlatform()->deleteCache('entity_storage_field_schema');
        $this->_application->getPlatform()->updateDatabase($this->_getSchema($fields), $this->_getSchema($fields, true));
    }
    
    public function delete(array $fields, $force = false)
    {
        $this->_application->getPlatform()->deleteCache('entity_storage_field_schema');
        if (!$force) {
            $field_schema_types = $this->_getFieldSchema('field_map');
        
            foreach ($fields as $field_name => $field) {
                if ($field->schema_type
                    && in_array($field->schema_type, $field_schema_types)
                ) {
                    // Field(s) with this field type still exist, do not delete
                    unset($fields[$field_name]);
                }
            }
        }
        
        if (!$schema = $this->_getSchema($fields)) return;

        try {
            $this->_application->getPlatform()->updateDatabase(null, $schema);
        } catch (\Exception $e) {
            $this->_application->logError($e);
        }
    }

    public function queryCount($entityType, Field\Query $fieldQuery, $limit = 0, $offset = 0)
    {
        $parsed = $this->_application->Filter('entity_storage_query', $this->parseQuery($entityType, $fieldQuery), array($entityType, $fieldQuery, true));   
        if ($parsed['group']) {
            $sql = sprintf(
                'SELECT %6$s, COUNT(%1$s) AS cnt FROM %2$s %3$s %4$s WHERE %5$s GROUP BY %6$s %7$s',
                $parsed['distinct'] ? 'DISTINCT(' . $parsed['table_id_column'] . ')' : $parsed['table_id_column'],
                $parsed['table_name'],
                $parsed['table_joins'],
                $parsed['count_joins'],
                $parsed['criteria'],
                $parsed['group'],
                $parsed['group_sort']
            );
            $rs = $this->_application->getDB()->query($sql, $limit, $offset);
            $ret = [];
            if (strpos($parsed['group'], ',')) { // group by multiple fields?
                foreach ($rs as $row) {
                    $count = array_pop($row);
                    eval('$ret["' . implode('"]["', $row) . '"] = $count;');  
                }
            } else {
                $it = $rs->getIterator();
                $it->rewind();
                while ($it->valid()) {
                    $row = $it->row();
                    $ret[$row[0]] = $row[1];
                    $it->next();
                }
            }

            return $ret;
        }
        
        $sql = sprintf(
            'SELECT COUNT(%s) FROM %s %s %s WHERE %s',
            $parsed['distinct'] ? 'DISTINCT(' . $parsed['table_id_column'] . ')' : $parsed['table_id_column'],
            $parsed['table_name'],
            $parsed['table_joins'],
            $parsed['count_joins'],
            $parsed['criteria']
        );

        return $this->_application->getDB()->query($sql)->fetchSingle();
    }

    /**
     * Fetch entity IDs by criteria
     * @param Field\Query $fieldQuery
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function query($entityType, Field\Query $fieldQuery, $limit = 20, $offset = 0, $saveEntityIds = false)
    {
        $hash = md5(serialize($fieldQuery));
        $parsed = $this->_application->Filter('entity_storage_query', $this->parseQuery($entityType, $fieldQuery, $hash), array($entityType, $fieldQuery, false));   
        $sql = sprintf(
            'SELECT %s %s AS id%s FROM %s %s %s WHERE %s %s',
            $parsed['distinct'] ? 'DISTINCT' : '',
            $parsed['table_id_column'],
            $parsed['extra_fields'] ? ', ' . implode(', ', $parsed['extra_fields']) : '',
            $parsed['table_name'],
            $parsed['table_joins'],
            $parsed['joins'],
            $parsed['criteria'],
            $parsed['sorts'] ? 'ORDER BY ' . implode(', ', $parsed['sorts']) : ''
        );
        if ($parsed['random_seed']) {
            $this->_application->getDB()->seedRandom($parsed['random_seed']);
        }
        $rs = $this->_application->getDB()->query($sql, $limit, $offset);
        $ret = [];
        if ($parsed['extra_fields']
            && $parsed['extra_fields_to_query']
        ) {
            foreach ($rs as $row) {
                foreach ($parsed['extra_fields_to_query'] as $column => $field_name) {
                    $ret[$row['id']][$field_name][$row[$field_name . '_weight']][$column] = $row[$column];
                }
            }
        } else {
            foreach ($rs as $row) {
                $ret[$row['id']] = $row['id'];
            }
        }
        
        if ($saveEntityIds) $this->_queries[$hash]['ids'] = array_keys($ret);

        return $ret;
    }

    public function parseQuery($entityType, Field\Query $fieldQuery, $hash = null)
    {
        if (!isset($hash)) $hash = md5(serialize($fieldQuery)) ;
        if (!isset($this->_queries[$hash])) {
            if (!isset($this->_parsers[$entityType])) {
                $this->_parsers[$entityType] = new QueryParser(
                    $this,
                    $entityType,
                    $this->_application->Entity_Types_impl($entityType)->entityTypeInfo()
                );
            }
            $this->_queries[$hash] = $this->_parsers[$entityType]->parse($fieldQuery);
        }
        return $this->_queries[$hash];
    }
    
    public function queryValueCount($entityType, Field\Query $fieldQuery, $fieldName, $valueColumn = null, array $otherColumns = null)
    {
        $hash = md5(serialize($fieldQuery));
        if (!isset($valueColumn)) $valueColumn = 'entity_id';
        $cache_id = 'drts-entity-query-facets-' . $entityType . $hash . $fieldName . $valueColumn;
        if (!$facets = $this->_application->getPlatform()->getCache($cache_id, 'content')) {
            if (null === $field_type = $this->getFieldSchemaType($fieldName)) { // may be empty string if property field
                return;
            }

            $facets = [];
            if (!isset($this->_queries[$hash]['ids'])) {
                $this->query($entityType, $fieldQuery, 0, 0, true);
            }
            if (!empty($this->_queries[$hash]['ids'])) {
                $db = $this->_application->getDB();
                if ($field_type === '') {
                    // Property field
                    $entity_type_info = $this->_application->Entity_Types_impl($entityType)->entityTypeInfo();
                    if (!isset($entity_type_info['properties'][$fieldName])) return;

                    $property = $entity_type_info['properties'][$fieldName];
                    $value_column = sprintf($valueColumn, $property['column']);
                    if (isset($property['field_name'])) {
                        $table = $db->getResourcePrefix() . $property['field_name'];
                        $id_column = 'entity_id';
                    } else {
                        $table = $entity_type_info['table_name'];
                        $id_column = $entity_type_info['properties']['id']['column'];
                    }
                    if (isset($otherColumns)) {
                        foreach ($otherColumns as $column_name => $column_value) {
                            $column_type = null;
                            if (isset($entity_type_info['properties'][$column_name])) {
                                $column_type = $entity_type_info['properties'][$column_name]['column_type'];
                                $column_name = $entity_type_info['properties'][$column_name]['column'];
                            } else {
                                $column_name = sprintf($column_name, $property['column']);
                            }
                            if (is_array($column_value)) {
                                foreach (array_keys($column_value) as $i) {
                                    $column_value[$i] = $this->escapeFieldValue($column_value[$i], $column_type);
                                }
                                $other_columns[] = $column_name . ' IN (' . implode(',', $column_value) . ')';
                            } else {
                                $column_value = $this->escapeFieldValue($column_value, $column_type);
                                $other_columns[] = $column_name . ' = ' . $column_value;
                            }
                        }
                    }
                    $sql = sprintf(
                        'SELECT %1$s AS _val, COUNT(DISTINCT %3$s) AS _cnt FROM %2$s WHERE %3$s IN (%4$s)%5$s GROUP BY _val',
                        $value_column,
                        $table,
                        $id_column,
                        implode(',', $this->_queries[$hash]['ids']),
                        empty($other_columns) ? '' : ' AND ' . implode(' AND ', $other_columns)
                    );
                } else {
                    if (isset($otherColumns)) {
                        $column_types = $this->getFieldColumnType($field_type);
                        foreach ($otherColumns as $column_name => $column_value) {
                            $column_type = null;
                            if (isset($column_types[$column_name])) {
                                $column_type = $column_types[$column_name];
                            }
                            if (is_array($column_value)) {
                                foreach (array_keys($column_value) as $i) {
                                    $column_value[$i] = $this->escapeFieldValue($column_value[$i], $column_type);
                                }
                                $other_columns[] = $column_name . ' IN (' . implode(',', $column_value) . ')';
                            } else {
                                $column_value = $this->escapeFieldValue($column_value, $column_type);
                                $other_columns[] = $column_name . ' = ' . $column_value;
                            }
                        }
                    }
                    $sql = sprintf(
                        'SELECT %1$s AS _val, COUNT(DISTINCT entity_id) AS _cnt FROM %2$sentity_field_%3$s WHERE entity_type = %4$s AND entity_id IN (%5$s) AND field_name = %6$s%7$s GROUP BY _val',
                        $valueColumn,
                        $db->getResourcePrefix(),
                        $field_type,
                        $db->escapeString($entityType),
                        implode(',', $this->_queries[$hash]['ids']),
                        $db->escapeString($fieldName),
                        empty($other_columns) ? '' : ' AND ' . implode(' AND ', $other_columns)
                    );
                }
                $rs = $db->query($sql);
                foreach ($rs as $row) {
                    $facets[$row['_val']] = $row['_cnt'];
                }
            }
            if (!isset($this->_fieldValueCountCacheLifetime)) {
                $this->_fieldValueCountCacheLifetime = $this->_application->Filter('entity_field_value_count_cache_lifetime', 3600); // cache 1 hour
            }
            $this->_application->getPlatform()->setCache($facets, $cache_id, $this->_fieldValueCountCacheLifetime, 'content');
        }
        
        return $facets;
    }

    private function _getSchema(array $fields, $old = false)
    {
        $default_columns = array(
            'entity_type' => array(
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'unsigned' => true,
                'length' => 40,
                'was' => 'entity_type',
                'default' => '',
            ),
            'bundle_name' => array(
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 40,
                'was' => 'bundle_name',
                'default' => '',
            ),
            'entity_id' => array(
                'type' => Application::COLUMN_INTEGER,
                'notnull' => true,
                'unsigned' => true,
                'was' => 'entity_id',
                'default' => 0,
            ),
            'field_name' => array(
                'type' => Application::COLUMN_VARCHAR,
                'notnull' => true,
                'length' => 150,
                'was' => 'field_name',
                'default' => '',
            ),
            'weight' => array(
                'type' => Application::COLUMN_INTEGER,
                'notnull' => true,
                'unsigned' => true,
                'was' => 'weight',
                'default' => 0,
            ),
        );
        $default_indexes = array(
            'primary' => array(
                'fields' => array(
                    'entity_type' => array('sorting' => 'ascending'),
                    'entity_id' => array('sorting' => 'ascending'),
                    'field_name' => array('sorting' => 'ascending'),
                    'weight' => array('sorting' => 'ascending'),
                ),
                'primary' => true,
                'was' => 'primary',
            ),
            'bundle_name' => array(
                'fields' => array('bundle_name' => array('sorting' => 'ascending')),
                'was' => 'bundle_name',
            ),
            'entity_id' => array(
                'fields' => array('entity_id' => array('sorting' => 'ascending')),
                'was' => 'entity_id',
            ),
            'weight' => array(
                'fields' => array('weight' => array('sorting' => 'ascending')),
                'was' => 'weight',
            ),
        );
        $tables = [];
        foreach ($fields as $field) {
            if ($old) {
                if (!isset($field->oldSchema)) continue;
                
                $field_schema = $field->oldSchema;
            } else {
                $field_schema = $field->schema;
            }
            if (empty($field_schema['columns'])) continue;
            
            $columns = $default_columns + $field_schema['columns'];
            $indexes = $default_indexes + (array)@$field_schema['indexes'];
            $tables['entity_field_' . $field->schema_type] = array(
                'comment' => sprintf('Field data table for %s', $field->type),
                'fields' => $columns,
                'indexes' => $indexes,
                'initialization' => [],
                'constraints' => [],
            );
        }

        if (!empty($tables)) {
            return array(
                'charset' => '',
                'description' => '',
                'tables' => $tables,
            );
        }
    }

    public function escapeFieldValue($value, $dataType = null)
    {
        switch ($dataType) {
            case Application::COLUMN_INTEGER:
                return intval($value);
            case Application::COLUMN_DECIMAL:
                return str_replace(',', '.', floatval($value));
            case Application::COLUMN_BOOLEAN:
                return $this->_application->getDB()->escapeBool($value);
            default:
                return $this->_application->getDB()->escapeString($value);
        }
    }
    
    public function getDB()
    {
        return $this->_application->getDB();
    }
    
    public function getFieldSchemaType($fieldName)
    {
        $field_map = $this->_getFieldSchema('field_map');
        return isset($field_map[$fieldName]) ? $field_map[$fieldName] : null;
    }
    
    public function getFieldColumnType($schemaType, $column = null)
    {
        $columns = $this->_getFieldSchema('columns');
        return isset($column) ? $columns[$schemaType][$column] : (isset($columns[$schemaType]) ? $columns[$schemaType] : null);
    }
    
    protected function _getFieldSchema($key)
    {
        if (!$ret = $this->_application->getPlatform()->getCache('entity_storage_field_schema')) {
            $ret = array('columns' => [], 'field_map' => []);
            foreach ($this->_application->getModel('FieldConfig', 'Entity')->fetch() as $field_config) {
                if (!$field_type = $this->_application->Field_Type($field_config->type, true)) continue;
                
                // Add field name to schema type map
                $ret['field_map'][$field_config->property ? $field_config->property : $field_config->name] = $field_config->schema_type;
                
                if (isset($ret['columns'][$field_config->schema_type])
                    || (!$field_schema = $field_type->fieldTypeSchema())
                    || !is_array($field_schema)
                ) continue;
                
                $ret['columns'][$field_config->schema_type] = [];
                foreach ($field_schema['columns'] as $clmn => $clmn_info) {
                    $ret['columns'][$field_config->schema_type][$clmn] = $clmn_info['type'];
                }
            }
            $this->_application->getPlatform()->setCache($ret, 'entity_storage_field_schema', 0);
        }

        return $ret[$key];
    }
}

use SabaiApps\Framework\Criteria;

class QueryParser implements Criteria\IVisitor
{
    protected $_storage, $_entityType, $_tableName, $_tableColumns, $_tableIdColumn, $_tableJoins, $_tables;
    
    public function __construct(Storage $storage, $entityType, array $entityTypeInfo)
    {
        $this->_storage = $storage;
        $this->_entityType = $entityType;
        $this->_tableName = $entityTypeInfo['table_name'];
        $this->_tableColumns = $entityTypeInfo['properties'];
        $this->_tableIdColumn = $this->_tableName . '.' . $entityTypeInfo['properties']['id']['column'];
        $this->_tableJoins = empty($entityTypeInfo['table_joins']) ? [] : $entityTypeInfo['table_joins'];
    }
    
    public function parse(Field\Query $fieldQuery)
    {      
        $this->_tables = $non_count_tables = [];
        
        $table_id_column = $fieldQuery->getTableIdColumn($this->_tableIdColumn);
        $table_joins = $fieldQuery->getTableJoins() ? $this->_tableJoins + $fieldQuery->getTableJoins() : $this->_tableJoins;
        if (!empty($table_joins)) {
            $_table_joins = [];
            foreach ($table_joins as $table_name => $table) {
                $_table_joins[$table['alias']] = sprintf(
                    'LEFT JOIN %1$s %2$s ON %2$s.%3$s',
                    $table_name,
                    $table['alias'],
                    sprintf($table['on'], $table['alias'], $this->_tableName, $table_id_column)
                );
            }
            $table_joins = implode(' ', $_table_joins);
        } else {
            $table_joins = '';
        }
        
        // Criteria
        $criteria = [];
        $fieldQuery->getCriteria()->acceptVisitor($this, $criteria);
        $criteria = implode(' ', $criteria);
        
        // Extra fields
        if ($extra_fields = $fieldQuery->getExtraFields()) {
            $extra_fields_to_query = [];
            foreach ($extra_fields as $column => $extra_field) {
                if (!$table = $this->_storage->getFieldSchemaType($extra_field['field_name'])) continue;
                
                $table_alias = $extra_field['field_name'];
                if (!isset($this->_tables[$table_alias])) {
                    $this->_tables[$table_alias] = array(
                        'name' => 'entity_field_' . $table,
                        'prefix' => true,
                        'field_name' => $extra_field['field_name'],
                    );
                    $non_count_tables[$table_alias] = $table;
                }
                $extra_fields[$column] = (isset($extra_field['sql']) ? $extra_field['sql'] : $table_alias . '.' . $column) . ' AS ' . $column;
                if (!empty($extra_field['query'])) {
                    $weight_column = $extra_field['field_name'] . '_weight';
                    $extra_fields[$weight_column] = $table_alias . '.weight AS ' . $weight_column;
                    $extra_fields_to_query[$column] = $extra_field['field_name'];
                }
            }
        } else {
            $extra_fields = $extra_fields_to_query = null;
        }

        // Sorts
        if ($sorts = $fieldQuery->getSorts()) {
            $_sorts = [];
            foreach ($sorts as $sort) {
                if (isset($sort['field_name'])) {
                    if ($this->_isProperty($sort['field_name'])) {
                        $_sorts[] = $this->_getPropertyColumn($sort['field_name']) . ' ' . $sort['order'];
                    } elseif (!empty($sort['is_extra_field'])) {
                        $_sorts[] = $sort['field_name'] . ' ' . $sort['order'];
                    } else {
                        if (!$table = $field_type = $this->_storage->getFieldSchemaType($sort['field_name'])) continue;
                        
                        $table_alias = isset($sort['table_alias']) ? $sort['table_alias'] : $sort['field_name'];
                        if (!isset($this->_tables[$table_alias])) {
                            $this->_tables[$table_alias] = array(
                                'name' => 'entity_field_' . $table,
                                'field_name' => $sort['field_name'],
                                'prefix' => true,
                            );
                            $non_count_tables[$table_alias] = $table;
                        }
                        $table_column = $table_alias . '.' . $sort['column'];
                        if (isset($sort['null_value'])) {
                            $null_value = $this->_storage->escapeFieldValue(
                                $sort['null_value'],
                                $this->_storage->getFieldColumnType($field_type, $sort['column'])
                            );
                            $_sorts[] = 'CASE WHEN ' . $table_column . ' IS NULL THEN ' . $null_value . ' ELSE ' . $table_column . ' END ' . $sort['order'];
                        } elseif (!empty($sort['empty_last'])) {
                            $_sorts[] = 'CASE WHEN ' . $table_column . ' IS NULL OR ' . $table_column . ' = 0 THEN 2 ELSE 1 END';
                        } elseif (!empty($sort['cases'])) {
                            $cases = '';
                            $column_type = $this->_storage->getFieldColumnType($field_type, $sort['column']);
                            $i = 0;
                            foreach ($sort['cases'] as $case_value) {
                                $case_value = $this->_storage->escapeFieldValue($case_value, $column_type);
                                $cases .= ' WHEN ' . $table_column . '=' . $case_value . ' THEN ' . ++$i;
                            }
                            $_sorts[] = 'CASE' . $cases . ' ELSE ' . ++$i . ' END';
                        } else {
                            $_sorts[] = $table_column . ' ' . $sort['order'];
                        }
                    }
                } else {
                    if (!empty($sort['is_random'])) {
                        $random_seed = $sort['random_seed'];
                        $_sorts[] = $this->_storage->getDB()->getRandomFunc($random_seed);
                    } elseif (!empty($sort['is_id'])) {
                        $_sorts[] = $table_id_column . ' ' . $sort['order'];
                    } elseif (!empty($sort['is_custom'])) {
                        $_sorts[] = call_user_func_array($sort['is_custom'], [$sort['order'], $this->_tableName, $table_id_column, &$this->_tables]);
                    }
                }
            }
            $sorts = $_sorts;
        } else {
            $sorts = null;
        }
           
        // Group
        if ($group = $fieldQuery->getGroup()) {
            $group_sort = isset($group['order']) ? 'ORDER BY cnt ' . $group['order'] : '';
            if (is_array($group['field_name'])) {
                $groups = [];
                foreach (array_keys($group['field_name']) as $key) {
                    if ($this->_isProperty($group['field_name'][$key])) {
                        $groups[] = $this->_getPropertyColumn($group['field_name'][$key]);
                    } elseif (empty($group['column'][$key])) { // column is empty if extra field
                        if (isset($extra_field[$group['field_name'][$key]])) {
                            $groups[] = $extra_field[$group['field_name'][$key]];
                        }
                    } else {
                        if ($_group = $this->_getGroupByFieldClause(
                            $group['field_name'][$key],
                            $group['column'][$key],
                            isset($group['table_alias'][$key]) ? $group['table_alias'][$key] : null
                        )) {
                            $groups[] = $_group;
                        }
                    }
                }
                $group = implode(', ', $groups);
            } else {
                if ($this->_isProperty($group['field_name'])) {
                    $group = $this->_getPropertyColumn($group['field_name']);
                } elseif (empty($group['column'])) { // column is empty if extra field
                    if (isset($extra_field[$group['field_name']])) {
                        $group = $extra_field[$group['field_name']];
                    }
                } else {
                    if ($_group = $this->_getGroupByFieldClause(
                        $group['field_name'],
                        $group['column'],
                        isset($group['table_alias']) ? $group['table_alias'] : null
                    )) {
                        $group = $_group;
                    }
                }
            }
        } else {
            $group = $group_sort = '';
        }

        // Table joins
        if (!empty($this->_tables)) {
            $table_prefix = $this->_storage->getDB()->getResourcePrefix();
            $entity_type = $this->_storage->getDB()->escapeString($this->_entityType);
            foreach ($this->_tables as $table_alias => $table) {
                if (!is_array($table)) {
                    $_joins[$table_alias] = sprintf(
                        'LEFT JOIN %1$sentity_field_%2$s %3$s ON %3$s.entity_id = %4$s AND %3$s.entity_type = %5$s',
                        $table_prefix,
                        $table,
                        $table_alias,
                        $table_id_column,
                        $entity_type
                    );
                } else {
                    if (isset($table['on'])) {
                        $_joins[$table_alias] = sprintf(
                            '%5$s JOIN %1$s%2$s %3$s ON %3$s.%4$s',
                            empty($table['prefix']) ? '' : $table_prefix,
                            $table['name'],
                            $table_alias,
                            sprintf($table['on'], $table_alias, $this->_tableName, $table_id_column, $entity_type, $this->_tableIdColumn),
                            isset($table['join_type']) ? $table['join_type'] : 'LEFT'
                        );
                    } else {
                        $format = '%6$s JOIN %1$s%2$s %3$s ON %3$s.entity_id = %4$s AND %3$s.entity_type = %5$s';
                        if (isset($table['field_name'])) {
                            $format .= ' AND %3$s.field_name = ' . $this->_storage->getDB()->escapeString($table['field_name']);
                        }
                        $_joins[$table_alias] = sprintf(
                            $format,
                            empty($table['prefix']) ? '' : $table_prefix,
                            $table['name'],
                            $table_alias,
                            $table_id_column,
                            $entity_type,
                            isset($table['join_type']) ? $table['join_type'] : 'LEFT'
                        );
                    }
                }
            }
            if (!empty($non_count_tables)) {
                $joins = implode(' ', $_joins);
                // For the count query, remove table joins that are used for sorting purpose only
                $count_joins = implode(' ', array_diff_key($_joins, $non_count_tables));
            } else {
                $joins = $count_joins = implode(' ', $_joins);
            }
        } else {
            $joins = $count_joins = '';
        }
        
        return array(
            'table_name' => $this->_tableName,
            'table_id_column' => $table_id_column,
            'table_joins' => $table_joins,
            'distinct' => $fieldQuery->isDistinct(),
            'criteria' => $criteria,
            'extra_fields' => $extra_fields,
            'extra_fields_to_query' => $extra_fields_to_query,
            'sorts' => $sorts,
            'random_seed' => isset($random_seed) ? $random_seed : null,
            'group' => $group,
            'group_sort' => $group_sort,
            'joins' => $joins,
            'count_joins' => $count_joins,
        );
    }

    protected function _getGroupByFieldClause($fieldName, $column, $tableAlias = null)
    {
        if (!$table = $this->_storage->getFieldSchemaType($fieldName)) return;

        $table_alias = isset($tableAlias) ? $tableAlias : $fieldName;
        if (!isset($this->_tables[$table_alias])) {
            $this->_tables[$table_alias] = $table;
        }
        return $table_alias . '.' . $column;
    }

    public function visitCriteriaEmpty(Criteria\EmptyCriteria $criteria, &$criterions)
    {
        $criterions[] = '1=1';
    }

    public function visitCriteriaComposite(Criteria\CompositeCriteria $criteria, &$criterions)
    {
        if ($criteria->isEmpty()) {
            $criterions[] = '1=1';
            return;
        }
        $elements = $criteria->getElements();
        $conditions = $criteria->getConditions();
        $criterions[] = '(';
        $result = false;
        foreach (array_keys($elements) as $i) {
            if ($result !== false) {
                $criterions[] = $conditions[$i];
            }
            $result = $elements[$i]->acceptVisitor($this, $criterions);	  
        }
        if ($result === false) {
            array_pop($criterions);
        }
        $criterions[] = ')';
    }

    public function visitCriteriaCompositeNot(Criteria\CompositeNotCriteria $criteria, &$criterions)
    {
        $criterions[] = 'NOT';
        $criterions[] = $this->visitCriteriaComposite($criteria, $criterions);
    }

    private function _visitCriteriaValue(Criteria\AbstractValueCriteria $criteria, &$criterions, $operator)
    {
        if (!$field = $this->_getField($criteria->getField())) return false;
        
        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + array(
                    'name' => $table_name,
                );
            }
        }
        $criterions[] = $field['column'];
        $criterions[] = $operator;
        $criterions[] = $this->_storage->escapeFieldValue($criteria->getValue(), $field['column_type']);
    }

    public function visitCriteriaIs(Criteria\IsCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '=');
    }

    public function visitCriteriaIsNot(Criteria\IsNotCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '!=');
    }

    public function visitCriteriaIsSmallerThan(Criteria\IsSmallerThanCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '<');
    }

    public function visitCriteriaIsGreaterThan(Criteria\IsGreaterThanCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '>');
    }

    public function visitCriteriaIsOrSmallerThan(Criteria\IsOrSmallerThanCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '<=');
    }

    public function visitCriteriaIsOrGreaterThan(Criteria\IsOrGreaterThanCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaValue($criteria, $criterions, '>=');
    }
    
    protected function _visitCriteriaIsNull(Criteria\AbstractCriteria $criteria, &$criterions, $null = true)
    {
        if (!$field = $this->_getField($criteria->getField())) return false;
        
        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + array(
                    'name' => $table_name,
                );
            }
        }
        $criterions[] = isset($field['column']) ? $field['column'] : 'entity_id';
        $criterions[] = $null ? 'IS NULL' : 'IS NOT NULL';
    }

    public function visitCriteriaIsNull(Criteria\IsNullCriteria $criteria, &$criterions, $null = true)
    {
        return $this->_visitCriteriaIsNull($criteria, $criterions);
    }

    public function visitCriteriaIsNotNull(Criteria\IsNotNullCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaIsNull($criteria, $criterions, false);
    }

    private function _visitCriteriaArray(Criteria\AbstractArrayCriteria $criteria, &$criterions, $format)
    {
        $values = $criteria->getArray();
        if (empty($values)
            || (!$field = $this->_getField($criteria->getField()))
        ) return false;
        
        $data_type = $field['column_type'];
        foreach (array_keys($values) as $k) {
            $values[$k] = $this->_storage->escapeFieldValue($values[$k], $data_type);
        }
        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + array(
                    'name' => $table_name,
                );
            }
        }
        $criterions[] = sprintf($format, $field['column'], implode(',', $values));
    }

    public function visitCriteriaIn(Criteria\InCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaArray($criteria, $criterions, '%s IN (%s)');
    }

    public function visitCriteriaNotIn(Criteria\NotInCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaArray($criteria, $criterions, '%s NOT IN (%s)');
    }

    private function _visitCriteriaString(Criteria\AbstractStringCriteria $criteria, &$criterions, $format, $operator = 'LIKE')
    {
        if (!$field = $this->_getField($criteria->getField())) return false;
        
        if (isset($field['tables'])) {
            foreach ($field['tables'] as $table_name => $table) {
                $this->_tables[$table['alias']] = $table + array(
                    'name' => $table_name,
                );
            }
        }
        $criterions[] = $field['column'];
        $criterions[] = $operator;
        $criterions[] = $this->_storage->escapeFieldValue(sprintf($format, $criteria->getString()), $field['column_type']);
    }

    public function visitCriteriaStartsWith(Criteria\StartsWithCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaString($criteria, $criterions, '%s%%');
    }

    public function visitCriteriaEndsWith(Criteria\EndsWithCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaString($criteria, $criterions, '%%%s');
    }

    public function visitCriteriaContains(Criteria\ContainsCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaString($criteria, $criterions, '%%%s%%');
    }
    
    public function visitCriteriaNotContains(Criteria\ContainsCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaString($criteria, $criterions, '%%%s%%', 'NOT LIKE');
    }

    private function _visitCriteriaField(Criteria\AbstractFieldCriteria $criteria, &$criterions, $operator)
    {
        $criterions[] = '1=1';
    }

    public function visitCriteriaIsField(Criteria\IsFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '=');
    }

    public function visitCriteriaIsNotField(Criteria\IsNotFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '!=');
    }

    public function visitCriteriaIsSmallerThanField(Criteria\IsSmallerThanFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '<');
    }

    public function visitCriteriaIsGreaterThanField(Criteria\IsGreaterThanFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '>');
    }

    public function visitCriteriaIsOrSmallerThanField(Criteria\IsOrSmallerThanFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '<=');
    }

    public function visitCriteriaIsOrGreaterThanField(Criteria\IsOrGreaterThanFieldCriteria $criteria, &$criterions)
    {
        return $this->_visitCriteriaField($criteria, $criterions, '>=');
    }
    
    private function _getPropertyColumn($fieldName)
    {
        if (!isset($this->_tableColumns[$fieldName]['column'])) return $fieldName;
        
        $column = $this->_tableColumns[$fieldName]['column'];
        if (strpos($column, '.')) return $column;
        
        $table_name = isset($this->_tableColumns[$fieldName]['field_name']) ? $this->_tableColumns[$fieldName]['field_name'] : $this->_tableName;
        return $table_name . '.' . $column;
    }
    
    protected function _isProperty($fieldName)
    {
        return isset($this->_tableColumns[$fieldName]);
    }
    
    protected function _getField(array $target)
    {
        // External table field
        if (!isset($target['field_name'])) {
            return isset($target['tables']) ? $target : null;
        }
        
        // Property field
        if (isset($this->_tableColumns[$target['field_name']])) {
            if (empty($this->_tableColumns[$target['field_name']])) return; // the entity type does not support this property
            
            $property = $this->_tableColumns[$target['field_name']];
            
            // Property field in an extra table, happens depending on the entity type
            if (isset($property['field_name'])) {
                if (!$field_type = $this->_storage->getFieldSchemaType($property['field_name'])) return;
                
                $table_alias = isset($target['table_alias']) ? $target['table_alias'] : $property['field_name'];
                return array(
                    'tables' => array(
                        'entity_field_' . $field_type => array(
                            'alias' => $table_alias,
                            'on' => $target['on'],
                            'prefix' => true,
                            'field_name' => $property['field_name'],
                        ),
                    ),
                    'column' => $table_alias . '.' . (isset($property['column']) ? $property['column'] : $target['column']),
                    'column_type' => $property['column_type'],
                );
            }

            return array(
                'column' => $this->_getPropertyColumn($target['field_name']),
                'column_type' => $property['column_type'],
            );
        }
        
        // Entity Field
        if (!$field_type = $this->_storage->getFieldSchemaType($target['field_name'])) return;
        
        $table_alias = isset($target['table_alias']) ? $target['table_alias'] : $target['field_name'];
        if (!isset($target['column'])) {
            $target['column'] = 'entity_id';
            $column_type = Application::COLUMN_INTEGER;
        } else {
            $column_type = $this->_storage->getFieldColumnType($field_type, $target['column']);
        }
        return array(
            'tables' => array(
                'entity_field_' . $field_type => array(
                    'alias' => $table_alias,
                    'on' => $target['on'],
                    'prefix' => true,
                    'field_name' => $target['field_name'],
                ),
            ),
            'column' => $table_alias . '.' . $target['column'],
            'column_type' => $column_type,
        );
    }
}