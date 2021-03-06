<?php
/* This file has been auto-generated. Do not edit this file directly. */
namespace SabaiApps\Directories\Component\Payment\Model\Base;

use SabaiApps\Framework\Model\Model;

abstract class FeatureGroupGateway extends \SabaiApps\Framework\Model\AbstractGateway
{
    public function getName()
    {
        return 'payment_featuregroup';
    }

    public function getFields()
    {
        return ['featuregroup_logs' => Model::KEY_TYPE_TEXT, 'featuregroup_bundle_name' => Model::KEY_TYPE_VARCHAR, 'featuregroup_order_id' => Model::KEY_TYPE_INT, 'featuregroup_id' => Model::KEY_TYPE_INT, 'featuregroup_created' => Model::KEY_TYPE_INT, 'featuregroup_updated' => Model::KEY_TYPE_INT];
    }

    protected function _getIdFieldName()
    {
        return 'featuregroup_id';
    }

    protected function _getSelectByIdQuery($id, $fields)
    {
        return sprintf(
            'SELECT %s FROM %spayment_featuregroup WHERE featuregroup_id = %d',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $id
        );
    }

    protected function _getSelectByIdsQuery($ids, $fields)
    {
        return sprintf(
            'SELECT %s FROM %spayment_featuregroup WHERE featuregroup_id IN (%s)',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            implode(', ', array_map('intval', $ids))
        );
    }

    protected function _getSelectByCriteriaQuery($criteriaStr, $fields)
    {
        return sprintf(
            'SELECT %1$s FROM %2$spayment_featuregroup payment_featuregroup WHERE %3$s',
            empty($fields) ? '*' : implode(', ', $fields),
            $this->_db->getResourcePrefix(),
            $criteriaStr
        );
    }

    protected function _getInsertQuery(&$values)
    {
        $values['featuregroup_created'] = time();
        $values['featuregroup_updated'] = 0;
        return sprintf('INSERT INTO %spayment_featuregroup(featuregroup_logs, featuregroup_bundle_name, featuregroup_order_id, featuregroup_id, featuregroup_created, featuregroup_updated) VALUES(%s, %s, %d, %s, %d, %d)', $this->_db->getResourcePrefix(), $this->_db->escapeString(serialize($values['featuregroup_logs'])), $this->_db->escapeString($values['featuregroup_bundle_name']), $values['featuregroup_order_id'], empty($values['featuregroup_id']) ? 'NULL' : intval($values['featuregroup_id']), $values['featuregroup_created'], $values['featuregroup_updated']);
    }

    protected function _getUpdateQuery($id, $values)
    {
        $last_update = $values['featuregroup_updated'];
        $values['featuregroup_updated'] = time();
        return sprintf('UPDATE %spayment_featuregroup SET featuregroup_logs = %s, featuregroup_bundle_name = %s, featuregroup_order_id = %d, featuregroup_updated = %d WHERE featuregroup_id = %d AND featuregroup_updated = %d', $this->_db->getResourcePrefix(), $this->_db->escapeString(serialize($values['featuregroup_logs'])), $this->_db->escapeString($values['featuregroup_bundle_name']), $values['featuregroup_order_id'], $values['featuregroup_updated'], $id, $last_update);
    }

    protected function _getDeleteQuery($id)
    {
        return sprintf('DELETE FROM %1$spayment_featuregroup WHERE featuregroup_id = %2$d', $this->_db->getResourcePrefix(), $id);
    }

    protected function _getUpdateByCriteriaQuery($criteriaStr, $sets)
    {
        $sets['featuregroup_updated'] = 'featuregroup_updated=' . time();
        return sprintf('UPDATE %spayment_featuregroup payment_featuregroup SET %s WHERE %s', $this->_db->getResourcePrefix(), implode(', ', $sets), $criteriaStr);
    }

    protected function _getDeleteByCriteriaQuery($criteriaStr)
    {
        return sprintf('DELETE payment_featuregroup, table1 FROM %1$spayment_featuregroup payment_featuregroup LEFT JOIN %1$spayment_feature table1 ON payment_featuregroup.featuregroup_id = table1.feature_featuregroup_id WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _getCountByCriteriaQuery($criteriaStr)
    {
        return sprintf('SELECT COUNT(*) FROM %1$spayment_featuregroup payment_featuregroup WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }

    protected function _beforeDelete1($id, array $old)
    {
        $this->_db->exec(sprintf('DELETE table0 FROM %1$spayment_feature table0 WHERE table0.feature_featuregroup_id = %2$d', $this->_db->getResourcePrefix(), $id));
    }

    protected function _beforeDelete($id, array $old)
    {
        $this->_beforeDelete1($id, $old);
    }
}