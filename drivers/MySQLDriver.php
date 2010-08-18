<?php
require_once DOKIN_DIR.'drivers/SQLBaseDriver.php';
require_once DOKIN_DIR.'DBDriver.php';

class MySQLDriver extends SQLBaseDriver
{
    private $db;

    protected function __construct($hConfig)
    {
        $this->db = mysql_connect($hConfig['host'], $hConfig['user'], $hConfig['password']) or _ERROR('Could not connect');
    }

    public function escape($sString)
    {
        return mysql_real_escape_string($sString, $this->db);
    }

    public function lastId()
    {
        return mysql_insert_id($this->db);
    }

    public function query($sQuery, $sClass)
    {
        print '[mysql] Query: '.$sQuery.PHP_EOL;
    }
}

