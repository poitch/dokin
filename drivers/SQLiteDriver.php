<?php
require_once DOKIN_DIR.'drivers/SQLBaseDriver.php';
require_once DOKIN_DIR.'drivers/SQLiteDBResult.php';

class SQLiteDriver extends SQLBaseDriver
{
    private $db;

    protected function __construct($hConfig)
    {
        $this->db = sqlite_open($hConfig['filename']);
    }

    public function escape($sString)
    {
        return sqlite_escape_string($sString);
    }

    public function lastId()
    {
        return sqlite_last_insert_rowid($this->db);
    }

    public function query($sQuery, $sClass)
    {
        print '[sqlite] Query '.$sQuery.PHP_EOL;
        $oRes = sqlite_query($this->db, $sQuery, SQLITE_ASSOC, $sError);
        if ($oRes === false) {
            throw new Exception($sError);
        }

        return new SQLiteDBResult($oRes, $sClass);
    }
}

