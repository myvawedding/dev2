<?php
/* This file has been auto-generated. Do not edit this file directly. */
namespace SabaiApps\Directories\Component\Entity\Model\Base;

use SabaiApps\Framework\Model\Model;

abstract class BundleGateway extends \SabaiApps\Framework\Model\AbstractGateway
{
    public function getName()
    {
        return 'entity_bundle';
    }

    public function getFields()
    {
        return ['bundle_name' => Model::KEY_TYPE_VARCHAR, 'bundle_type' => Model::KEY_TYPE_VARCHAR, 'bundle_component' => Model::KEY_TYPE_VARCHAR, 'bundle_info' => Model::KEY_TYPE_TEXT, 'bundle_entitytype_name' => Model::KEY_TYPE_VARCHAR, 'bundle_group' => Model::KEY_TYPE_VARCHAR, 'bundle_created' => Model::KEY_TYPE_INT, 'bundle_updated' => Model::KEY_TYPE_INT];
    }

    protected function _getIdFieldName()
    {
        return 'bundle_name';
    }

    protected function _getSelectByIdQuery($id, $fields)
    {
        return sprintf(
            'SELECT %s FROM %sentity_bundle WHERE bundle_name = %s',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($id)
        );
    }

    protected function _getSelectByIdsQuery($ids, $fields)
    {
        return sprintf(
            'SELECT %s FROM %sentity_bundle WHERE bundle_name IN (%s)',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            implode(', ', array_map(array($this->_db, 'escapeString'), $ids))
        );
    }

    protected function _getSelectByCriteriaQuery($criteriaStr, $fields)
    {
        return sprintf(
            'SELECT %1$s FROM %2$sentity_bundle entity_bundle WHERE %3$s',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $criteriaStr
        );
    }

    protected function _getInsertQuery(&$values)
    {
        $values['bundle_created'] = time();
        $values['bundle_updated'] = 0;
        return sprintf('INSERT INTO %sentity_bundle(bundle_name, bundle_type, bundle_component, bundle_info, bundle_entitytype_name, bundle_group, bundle_created, bundle_updated) VALUES(%s, %s, %s, %s, %s, %s, %d, %d)', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['bundle_name']), $this->_db->escapeString($values['bundle_type']), $this->_db->escapeString($values['bundle_component']), $this->_db->escapeString(serialize($values['bundle_info'])), $this->_db->escapeString($values['bundle_entitytype_name']), $this->_db->escapeString($values['bundle_group']), $values['bundle_created'], $values['bundle_updated']);
    }

    protected function _getUpdateQuery($id, $values)
    {
        $last_update = $values['bundle_updated'];
        $values['bundle_updated'] = time();
        return sprintf('UPDATE %sentity_bundle SET bundle_type = %s, bundle_component = %s, bundle_info = %s, bundle_entitytype_name = %s, bundle_group = %s, bundle_updated = %d WHERE bundle_name = %s AND bundle_updated = %d', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['bundle_type']), $this->_db->escapeString($values['bundle_component']), $this->_db->escapeString(serialize($values['bundle_info'])), $this->_db->escapeString($values['bundle_entitytype_name']), $this->_db->escapeString($values['bundle_group']), $values['bundle_updated'], $this->_db->escapeString($id), $last_update);
    }

    protected function _getDeleteQuery($id)
    {
        return sprintf('DELETE FROM %1$sentity_bundle WHERE bundle_name = %2$s', $this->_db->getResourcePrefix(), $this->_db->escapeString($id));
    }

    protected function _getUpdateByCriteriaQuery($criteriaStr, $sets)
    {
        $sets['bundle_updated'] = 'bundle_updated=' . time();
        return sprintf('UPDATE %sentity_bundle entity_bundle SET %s WHERE %s', $this->_db->getResourcePrefix(), implode(', ', $sets), $criteriaStr);
    }

    protected function _getDeleteByCriteriaQuery($criteriaStr)
    {
        return sprintf('DELETE entity_bundle, table1 FROM %1$sentity_bundle entity_bundle LEFT JOIN %1$sentity_field table1 ON entity_bundle.bundle_name = table1.field_bundle_name WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _getCountByCriteriaQuery($criteriaStr)
    {
        return sprintf('SELECT COUNT(*) FROM %1$sentity_bundle entity_bundle WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _beforeDelete1($id, array $old)
    {
        $this->_db->exec(sprintf('DELETE table0 FROM %1$sentity_field table0 WHERE table0.field_bundle_name = %2$s', $this->_db->getResourcePrefix(), $this->_db->escapeString($id)));
    }

    protected function _beforeDelete($id, array $old)
    {
        $this->_beforeDelete1($id, $old);
    }
}