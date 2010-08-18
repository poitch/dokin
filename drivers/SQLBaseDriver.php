<?php
require_once DOKIN_DIR.'DBDriver.php';

abstract class SQLBaseDriver extends DBDriver
{
    private $db;

    protected function __construct($hConfig)
    {
        $this->db = sqlite_open($hConfig['filename']);
    }

    public function exec($oModel)
    {
        $hTree = $oModel->getTree();

        $sClass = get_class($oModel);
        $sTable = $oModel->getTable();
        $sCommand = $hTree['COMMAND'];
        $hWhere = $hTree['WHERE'];
        $hSet = $hTree['SET'];
        $sIdField = $hTree['IDFIELD'];

        $sQuery = '';
        if ($sCommand == SQL_COMMAND_SELECT) {
            $sQuery .= 'SELECT * FROM '.$sTable;
        } else if ($sCommand == SQL_COMMAND_UPDATE) {
            $sQuery .= 'UPDATE '.$sTable;
        } else if ($sCommand == SQL_COMMAND_DELETE) {
            $sQuery .= 'DELETE FROM '.$sTable;
        }

        if (is_array($hSet) && sizeof($hSet)) {
            $aSetParts = array();
            foreach ($hSet as $sKey => $sValue) {
                if ($sValue === NULL) {
                    $aSetParts[] = $sKey . ' IS NULL';
                } else if (is_numeric($sValue)) {
                    $aSetParts[] = $sKey.'='.$this->escape($sValue);
                } else {
                    $aSetParts[] = $sKey.'=\''.$this->escape($sValue).'\'';
                }
            }

            $sQuery .= ' SET '.implode(',', $aSetParts);
        }

        if (is_array($hWhere) && sizeof($hWhere)) {
            $aWhereParts = array();
            foreach ($hWhere as $sKey => $sValue) {
                if ($sValue === NULL) {
                    $aWhereParts[] = $sKey . ' IS NULL';
                } else if (is_numeric($sValue)) {
                    $aWhereParts[] = $sKey.'='.$this->escape($sValue);
                } else {
                    $aWhereParts[] = $sKey.'=\''.$this->escape($sValue).'\'';
                }
            }

            $sQuery .= ' WHERE '.implode(' AND ', $aWhereParts);
        } else if ($sCommand == SQL_COMMAND_UPDATE || $sCommand == SQL_COMMAND_DELETE) {
            if ($oModel->$sIdField !== null) {
                $sQuery .= ' WHERE '.$sIdField.'='.$oModel->$sIdField;
            }
        }

        return $this->query($sQuery, $sClass);
    }

}


