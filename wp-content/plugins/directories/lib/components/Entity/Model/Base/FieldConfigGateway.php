<?php
/* This file has been auto-generated. Do not edit this file directly. */
namespace SabaiApps\Directories\Component\Entity\Model\Base;

use SabaiApps\Framework\Model\Model;

abstract class FieldConfigGateway extends \SabaiApps\Framework\Model\AbstractGateway
{
    public function getName()
    {
        return 'entity_fieldconfig';
    }

    public function getFields()
    {
        return ['fieldconfig_name' => Model::KEY_TYPE_VARCHAR, 'fieldconfig_type' => Model::KEY_TYPE_VARCHAR, 'fieldconfig_system' => Model::KEY_TYPE_INT, 'fieldconfig_settings' => Model::KEY_TYPE_TEXT, 'fieldconfig_property' => Model::KEY_TYPE_VARCHAR, 'fieldconfig_schema' => Model::KEY_TYPE_TEXT, 'fieldconfig_schema_type' => Model::KEY_TYPE_VARCHAR, 'fieldconfig_entitytype_name' => Model::KEY_TYPE_VARCHAR, 'fieldconfig_bundle_type' => Model::KEY_TYPE_VARCHAR, 'fieldconfig_created' => Model::KEY_TYPE_INT, 'fieldconfig_updated' => Model::KEY_TYPE_INT];
    }

    protected function _getIdFieldName()
    {
        return 'fieldconfig_name';
    }

    protected function _getSelectByIdQuery($id, $fields)
    {
        return sprintf(
            'SELECT %s FROM %sentity_fieldconfig WHERE fieldconfig_name = %s',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($id)
        );
    }

    protected function _getSelectByIdsQuery($ids, $fields)
    {
        return sprintf(
            'SELECT %s FROM %sentity_fieldconfig WHERE fieldconfig_name IN (%s)',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            implode(', ', array_map(array($this->_db, 'escapeString'), $ids))
        );
    }

    protected function _getSelectByCriteriaQuery($criteriaStr, $fields)
    {
        return sprintf(
            'SELECT %1$s FROM %2$sentity_fieldconfig entity_fieldconfig WHERE %3$s',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $criteriaStr
        );
    }

    protected function _getInsertQuery(&$values)
    {
        $values['fieldconfig_created'] = time();
        $values['fieldconfig_updated'] = 0;
        return sprintf('INSERT INTO %sentity_fieldconfig(fieldconfig_name, fieldconfig_type, fieldconfig_system, fieldconfig_settings, fieldconfig_property, fieldconfig_schema, fieldconfig_schema_type, fieldconfig_entitytype_name, fieldconfig_bundle_type, fieldconfig_created, fieldconfig_updated) VALUES(%s, %s, %d, %s, %s, %s, %s, %s, %s, %d, %d)', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['fieldconfig_name']), $this->_db->escapeString($values['fieldconfig_type']), $values['fieldconfig_system'], $this->_db->escapeString(serialize($values['fieldconfig_settings'])), $this->_db->escapeString($values['fieldconfig_property']), $this->_db->escapeString(serialize($values['fieldconfig_schema'])), $this->_db->escapeString($values['fieldconfig_schema_type']), $this->_db->escapeString($values['fieldconfig_entitytype_name']), $this->_db->escapeString($values['fieldconfig_bundle_type']), $values['fieldconfig_created'], $values['fieldconfig_updated']);
    }

    protected function _getUpdateQuery($id, $values)
    {
        $last_update = $values['fieldconfig_updated'];
        $values['fieldconfig_updated'] = time();
        return sprintf('UPDATE %sentity_fieldconfig SET fieldconfig_type = %s, fieldconfig_system = %d, fieldconfig_settings = %s, fieldconfig_property = %s, fieldconfig_schema = %s, fieldconfig_schema_type = %s, fieldconfig_entitytype_name = %s, fieldconfig_bundle_type = %s, fieldconfig_updated = %d WHERE fieldconfig_name = %s AND fieldconfig_updated = %d', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['fieldconfig_type']), $values['fieldconfig_system'], $this->_db->escapeString(serialize($values['fieldconfig_settings'])), $this->_db->escapeString($values['fieldconfig_property']), $this->_db->escapeString(serialize($values['fieldconfig_schema'])), $this->_db->escapeString($values['fieldconfig_schema_type']), $this->_db->escapeString($values['fieldconfig_entitytype_name']), $this->_db->escapeString($values['fieldconfig_bundle_type']), $values['fieldconfig_updated'], $this->_db->escapeString($id), $last_update);
    }

    protected function _getDeleteQuery($id)
    {
        return sprintf('DELETE FROM %1$sentity_fieldconfig WHERE fieldconfig_name = %2$s', $this->_db->getResourcePrefix(), $this->_db->escapeString($id));
    }

    protected function _getUpdateByCriteriaQuery($criteriaStr, $sets)
    {
        $sets['fieldconfig_updated'] = 'fieldconfig_updated=' . time();
        return sprintf('UPDATE %sentity_fieldconfig entity_fieldconfig SET %s WHERE %s', $this->_db->getResourcePrefix(), implode(', ', $sets), $criteriaStr);
    }

    protected function _getDeleteByCriteriaQuery($criteriaStr)
    {
        return sprintf('DELETE entity_fieldconfig, table1 FROM %1$sentity_fieldconfig entity_fieldconfig LEFT JOIN %1$sentity_field table1 ON entity_fieldconfig.fieldconfig_name = table1.field_fieldconfig_name WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _getCountByCriteriaQuery($criteriaStr)
    {
        return sprintf('SELECT COUNT(*) FROM %1$sentity_fieldconfig entity_fieldconfig WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _beforeDelete1($id, array $old)
    {
        $this->_db->exec(sprintf('DELETE table0 FROM %1$sentity_field table0 WHERE table0.field_fieldconfig_name = %2$s', $this->_db->getResourcePrefix(), $this->_db->escapeString($id)));
    }

    protected function _beforeDelete($id, array $old)
    {
        $this->_beforeDelete1($id, $old);
    }
}