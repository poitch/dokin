<?php
require_once DOKIN_DIR.'drivers/SQLiteDriver.php';
require_once DOKIN_DIR.'drivers/MySQLDriver.php';
require_once DOKIN_DIR.'drivers/MongoDriver.php';

abstract class DBDriver
{
    abstract public function exec($oModel);
    abstract public function query($sQuery, $sClass);
    abstract public function escape($sString);
    abstract public function lastId();

    abstract protected function __construct($hConfig);

    public static function get_instance($sDriver, $hConfig = array())
    {
        switch($sDriver) {
            case 'sqlite':
                return new SQLiteDriver($hConfig);
            case 'mysql':
                return new MySQLDriver($hConfig);
            case 'mongo':
                return new MongoDriver($hConfig);
        }
    }
}

