<?php
require_once DOKIN_DIR.'drivers/MongoDBResult.php';

class MongoDriver extends DBDriver
{
    private $db;

    protected function __construct($hConfig)
    {
        if (!extension_loaded('mongo')) {
            throw new Exception('Mongo extension not installed/enabled');
        }
        $this->db = new Mongo();
    }

    public function exec($oModel)
    {
        $sDB = $oModel->getDB();
        $sClass = get_class($oModel);
        $sCollection = $oModel->getTable();
        $hTree = $oModel->getTree();
        $sCommand = $hTree['COMMAND'];
        $hWhere = $hTree['WHERE'];

        $hWhere = is_array($hWhere) ? $hWhere : array();

        if ($sCommand == SQL_COMMAND_SELECT) {
            $oCursor = $this->db->$sDB->$sCollection->find($hWhere);
            return new MongoDBResult($oCursor, $sClass);
        } else if ($sCommand == SQL_COMMAND_INSERT || $sCommand == SQL_COMMAND_UPDATE) {
            $hObj = $oModel->toHash();
            $mRes = $this->db->$sDB->$sCollection->save($hObj, array('safe' => true));
            if (is_object($hObj)) {
                $oModel->_id = $hObj['_id']->__toString();
            }
            return $oModel;
        }
    }

    public function query($sQuery, $sClass)
    {
    }

    public function escape($sString)
    {
    }

    public function lastId()
    {
    }
}

