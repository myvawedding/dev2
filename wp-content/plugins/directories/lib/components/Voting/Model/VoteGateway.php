<?php
namespace SabaiApps\Directories\Component\Voting\Model;

class VoteGateway extends Base\VoteGateway
{
    public function getResults($bundleName, $entityId, $fieldName)
    {      
        $sql = sprintf(
            'SELECT vote_entity_id, vote_name, COUNT(*) AS cnt, SUM(vote_value) AS sm, MAX(vote_created) AS mx'
                . ' FROM %svoting_vote WHERE vote_bundle_name = %s AND vote_entity_id IN (%s) AND vote_field_name = %s'
                . ' GROUP BY vote_name',
             $this->_db->getResourcePrefix(),
             $this->_db->escapeString($bundleName),
             implode(',', array_map(array($this->_db, 'escapeString'), (array)$entityId)),
             $this->_db->escapeString($fieldName)
        );
        $rs = $this->_db->query($sql);
        $ret = [];
        foreach ($rs as $row) {
            $ret[$row['vote_entity_id']][$row['vote_name']] = array('count' => (int)$row['cnt'], 'sum' => $row['sm'], 'last_voted_at' => $row['mx']);
        }
        
        return is_array($entityId) ? $ret : (isset($ret[$entityId]) ? $ret[$entityId] : null);
    }
    
    public function getVotes($bundleName, array $entityIds, $userId, array $fieldNames = null)
    {
        $sql = sprintf(
            'SELECT vote_field_name, vote_entity_id, vote_value FROM %svoting_vote WHERE vote_bundle_name = %s AND vote_entity_id IN (%s) AND vote_user_id = %d %s %s',
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($bundleName),
            implode(',', array_map('intval', $entityIds)),
            $userId,
            isset($fieldNames) ? sprintf('AND vote_field_name IN (%s)', implode(',', array_map(array($this->_db, 'escapeString'), $fieldNames))) : '',
            empty($userId) ? sprintf('AND vote_ip = %s', $this->_db->escapeString($this->_getIp())) : ''
        );
        $rs = $this->_db->query($sql);
        $ret = [];
        foreach ($rs as $row) {
            $ret[$row['vote_field_name']][$row['vote_entity_id']] = $row['vote_value'];
        }
        return $ret;
    }
    
    public function countByLevel($bundleName, $entityId, $fieldName, $name)
    {
        $sql = sprintf("
SELECT vote_level, COUNT(*) AS cnt
FROM %svoting_vote
WHERE vote_bundle_name = %s AND vote_entity_id = %d AND vote_field_name = %s AND vote_name = %s
GROUP BY vote_level",
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($bundleName),
            $entityId,
            $this->_db->escapeString($fieldName),
            $this->_db->escapeString($name)
        );
        $rs = $this->_db->query($sql);
        $ret = [];
        foreach ($rs as $row) {
            $ret[$row['vote_level']] = $row['cnt'];
        }
        return $ret;
    }
    
    private function _getIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        return '';
    }
}