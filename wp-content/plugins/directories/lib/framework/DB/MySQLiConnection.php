<?php
namespace SabaiApps\Framework\DB;

use SabaiApps\Framework\Exception;

class MySQLiConnection extends MySQLConnection
{    
    protected function _doConnect()
    {
        $this->_clientFlags = $this->_resourceSecure ? MYSQLI_CLIENT_FOUND_ROWS | MYSQLI_CLIENT_SSL : MYSQLI_CLIENT_FOUND_ROWS;
        $link = mysqli_init();
        if (!mysqli_real_connect(
            $link,
            $this->_resourceHost,
            $this->_resourceUser,
            $this->_resourceUserPassword,
            $this->_resourceName,
            $this->_resourcePort,
            null,
            $this->_clientFlags
        )) {
            throw new Exception(sprintf('Unable to connect to database server. Error: %s(%s)', mysqli_connect_error(), mysqli_connect_errno()));
        }
        mysqli_autocommit($link, true);
        
        // Set client encoding if requested
        if (!empty($this->_charset)
            && ($encoding = $this->_getClientEncoding($this->_charset))
        ) {
            mysqli_set_charset($link, $encoding);
        }
        return $link;
    }
    
    public function getDSN()
    {
        return sprintf('mysqli://%s:%s@%s:%s/%s?client_flags=%d',
            rawurlencode($this->_resourceUser),
            rawurlencode($this->_resourceUserPassword),
            rawurlencode($this->_resourceHost),
            rawurlencode($this->_resourcePort),
            rawurlencode($this->_resourceName),
            $this->_clientFlags
        );
    }
}