<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Framework\DB\MySQL;
use SabaiApps\Framework\DB\MySQLRowset;
use SabaiApps\Framework\DB\AbstractConnection;

class DB extends MySQL
{
    protected $_affectedRows;
    
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct(new DBConnection($wpdb), $wpdb->prefix . 'drts_');
    }
    
    protected function _doQuery($query)
    {
        $wpdb = $this->_connection->getWpdb();
        $wpdb->hide_errors(); // query errors are handled by exceptions, so do not print them out
        $result = $wpdb->query($query);
        $wpdb->show_errors();

        return false === $result ? false : new DBRowset($wpdb->last_result);
    }

    protected function _doExec($sql)
    {
        $wpdb = $this->_connection->getWpdb();
        $wpdb->hide_errors(); // query errors are handled by exceptions, so do not print them out
        $result = $wpdb->query($sql);
        $wpdb->show_errors();
        if (false === $result) {
            $this->_affectedRows = -1;
            return false;
        }      
        
        $this->_affectedRows = $result;
        return true;
    }

    public function affectedRows()
    {
        return $this->_affectedRows;
    }

    public function lastInsertId($tableName, $keyName)
    {
        return $this->_connection->getWpdb()->insert_id;
    }

    public function lastError()
    {
        return $this->_connection->getWpdb()->last_error;
    }

    public function escapeString($value)
    {
        return "'" . $this->_connection->getWpdb()->_real_escape($value) . "'";
    }
}

class DBRowset extends MySQLRowset
{
    protected $_rowIndex = 0;
    
    public function fetchColumn($index = 0)
    {
        if (!isset($this->_rs[$this->_rowIndex])) return false;
        
        $keys = array_keys((array)$this->_rs[$this->_rowIndex]);
        $key = $keys[$index];
        return $this->_rs[$this->_rowIndex]->$key;
    }

    public function fetchAllColumns($index = 0)
    {
        if (!isset($this->_rs[0])) return [];
        
        $keys = array_keys((array)$this->_rs[0]);
        $key = $keys[$index];
        $ret = array($this->_rs[0]->$key);
        $count = count($this->_rs);
        for ($i = 1; $i < $count; ++$i) {
            $ret[] = $this->_rs[$i]->$key;
        }

        return $ret;
    }

    public function fetchRow()
    {
        return array_values((array)$this->_rs[$this->_rowIndex]);
    }

    public function fetchAssoc()
    {
        return (array)$this->_rs[$this->_rowIndex];
    }

    public function seek($rowNum = 0)
    {
        $this->_rowIndex = $rowNum;
        return isset($this->_rs[$this->_rowIndex]);
    }

    public function rowCount()
    {
        return count($this->_rs);
    }
}

class DBConnection extends AbstractConnection
{
    protected $_wpdb;
    
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb->use_mysqli ? 'MySQLi' : 'MySQL');
        $this->_resourceName = $wpdb->dbname;
        $this->_wpdb = $wpdb;
    }
    
    public function getWpdb()
    {
        return $this->_wpdb;
    }

    protected function _doConnect()
    {
        return $this->_wpdb->dbh;
    }

    public function getDSN()
    {
        return sprintf('%s://%s:%s@%s/%s?client_flags=%d',
            strtolower($this->_scheme),
            rawurlencode($this->_wpdb->dbuser),
            rawurlencode($this->_wpdb->dbpassword),
            rawurlencode($this->_wpdb->dbhost),
            rawurlencode($this->_wpdb->dbname),
            $this->_scheme === 'MySQL'
                ? (defined('MYSQL_CLIENT_FLAGS') ? MYSQL_CLIENT_FLAGS : 0)
                : (defined('MYSQLI_CLIENT_FLAGS') ? MYSQLI_CLIENT_FLAGS : 0)
        );
    }
}