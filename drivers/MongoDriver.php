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
        $hSafe = array('safe' => true);

        if ($sCommand == SQL_COMMAND_SELECT) {
            $oCursor = $this->db->$sDB->$sCollection->find($hWhere);
            return new MongoDBResult($oCursor, $sClass);
        } else if ($sCommand == SQL_COMMAND_INSERT || $sCommand == SQL_COMMAND_UPDATE) {
            $hObj = $oModel->toHash();
            print $sCommand.print_r($hObj, 1);
            $mRes = $this->db->$sDB->$sCollection->save($hObj, $hSafe);
            if (is_object($hObj['_id'])) {
                $oModel->_id = $hObj['_id']->__toString();
            }
            return $oModel;
        } else if ($sCommand == SQL_COMMAND_DELETE) {
            if (isset($hWhere['_id'])) {
                $hWhere['_id'] = new MongoId($hWhere['_id']);
            }
            $res = $this->db->$sDB->$sCollection->remove($hWhere, $hSafe);
            if (!$res['ok']) {
                throw new Exception($res['err']);
            }
            return true;
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

