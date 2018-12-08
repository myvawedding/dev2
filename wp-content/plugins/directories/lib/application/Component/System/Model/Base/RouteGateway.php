<?php
/* This file has been auto-generated. Do not edit this file directly. */
namespace SabaiApps\Directories\Component\System\Model\Base;

use SabaiApps\Framework\Model\Model;

abstract class RouteGateway extends \SabaiApps\Framework\Model\AbstractGateway
{
    public function getName()
    {
        return 'system_route';
    }

    public function getFields()
    {
        return ['route_path' => Model::KEY_TYPE_VARCHAR, 'route_method' => Model::KEY_TYPE_VARCHAR, 'route_format' => Model::KEY_TYPE_TEXT, 'route_controller' => Model::KEY_TYPE_VARCHAR, 'route_controller_component' => Model::KEY_TYPE_VARCHAR, 'route_forward' => Model::KEY_TYPE_VARCHAR, 'route_component' => Model::KEY_TYPE_VARCHAR, 'route_type' => Model::KEY_TYPE_INT, 'route_access_callback' => Model::KEY_TYPE_BOOL, 'route_title_callback' => Model::KEY_TYPE_BOOL, 'route_callback_path' => Model::KEY_TYPE_VARCHAR, 'route_callback_component' => Model::KEY_TYPE_VARCHAR, 'route_weight' => Model::KEY_TYPE_INT, 'route_depth' => Model::KEY_TYPE_INT, 'route_priority' => Model::KEY_TYPE_INT, 'route_data' => Model::KEY_TYPE_TEXT, 'route_language' => Model::KEY_TYPE_VARCHAR, 'route_admin' => Model::KEY_TYPE_BOOL, 'route_id' => Model::KEY_TYPE_INT, 'route_created' => Model::KEY_TYPE_INT, 'route_updated' => Model::KEY_TYPE_INT];
    }

    protected function _getIdFieldName()
    {
        return 'route_id';
    }

    protected function _getSelectByIdQuery($id, $fields)
    {
        return sprintf(
            'SELECT %s FROM %ssystem_route WHERE route_id = %d',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $id
        );
    }

    protected function _getSelectByIdsQuery($ids, $fields)
    {
        return sprintf(
            'SELECT %s FROM %ssystem_route WHERE route_id IN (%s)',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            implode(', ', array_map('intval', $ids))
        );
    }

    protected function _getSelectByCriteriaQuery($criteriaStr, $fields)
    {
        return sprintf(
            'SELECT %1$s FROM %2$ssystem_route system_route WHERE %3$s',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $criteriaStr
        );
    }

    protected function _getInsertQuery(&$values)
    {
        $values['route_created'] = time();
        $values['route_updated'] = 0;
        return sprintf('INSERT INTO %ssystem_route(route_path, route_method, route_format, route_controller, route_controller_component, route_forward, route_component, route_type, route_access_callback, route_title_callback, route_callback_path, route_callback_component, route_weight, route_depth, route_priority, route_data, route_language, route_admin, route_id, route_created, route_updated) VALUES(%s, %s, %s, %s, %s, %s, %s, %d, %u, %u, %s, %s, %d, %d, %d, %s, %s, %u, %s, %d, %d)', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['route_path']), $this->_db->escapeString($values['route_method']), $this->_db->escapeString(serialize($values['route_format'])), $this->_db->escapeString($values['route_controller']), $this->_db->escapeString($values['route_controller_component']), $this->_db->escapeString($values['route_forward']), $this->_db->escapeString($values['route_component']), $values['route_type'], $this->_db->escapeBool($values['route_access_callback']), $this->_db->escapeBool($values['route_title_callback']), $this->_db->escapeString($values['route_callback_path']), $this->_db->escapeString($values['route_callback_component']), $values['route_weight'], $values['route_depth'], $values['route_priority'], $this->_db->escapeString(serialize($values['route_data'])), $this->_db->escapeString($values['route_language']), $this->_db->escapeBool($values['route_admin']), empty($values['route_id']) ? 'NULL' : intval($values['route_id']), $values['route_created'], $values['route_updated']);
    }

    protected function _getUpdateQuery($id, $values)
    {
        $last_update = $values['route_updated'];
        $values['route_updated'] = time();
        return sprintf('UPDATE %ssystem_route SET route_path = %s, route_method = %s, route_format = %s, route_controller = %s, route_controller_component = %s, route_forward = %s, route_component = %s, route_type = %d, route_access_callback = %u, route_title_callback = %u, route_callback_path = %s, route_callback_component = %s, route_weight = %d, route_depth = %d, route_priority = %d, route_data = %s, route_language = %s, route_admin = %u, route_updated = %d WHERE route_id = %d AND route_updated = %d', $this->_db->getResourcePrefix(), $this->_db->escapeString($values['route_path']), $this->_db->escapeString($values['route_method']), $this->_db->escapeString(serialize($values['route_format'])), $this->_db->escapeString($values['route_controller']), $this->_db->escapeString($values['route_controller_component']), $this->_db->escapeString($values['route_forward']), $this->_db->escapeString($values['route_component']), $values['route_type'], $this->_db->escapeBool($values['route_access_callback']), $this->_db->escapeBool($values['route_title_callback']), $this->_db->escapeString($values['route_callback_path']), $this->_db->escapeString($values['route_callback_component']), $values['route_weight'], $values['route_depth'], $values['route_priority'], $this->_db->escapeString(serialize($values['route_data'])), $this->_db->escapeString($values['route_language']), $this->_db->escapeBool($values['route_admin']), $values['route_updated'], $id, $last_update);
    }

    protected function _getDeleteQuery($id)
    {
        return sprintf('DELETE FROM %1$ssystem_route WHERE route_id = %2$d', $this->_db->getResourcePrefix(), $id);
    }

    protected function _getUpdateByCriteriaQuery($criteriaStr, $sets)
    {
        $sets['route_updated'] = 'route_updated=' . time();
        return sprintf('UPDATE %ssystem_route system_route SET %s WHERE %s', $this->_db->getResourcePrefix(), implode(', ', $sets), $criteriaStr);
    }

    protected function _getDeleteByCriteriaQuery($criteriaStr)
    {
        return sprintf('DELETE system_route FROM %1$ssystem_route system_route WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _getCountByCriteriaQuery($criteriaStr)
    {
        return sprintf('SELECT COUNT(*) FROM %1$ssystem_route system_route WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }
}