<?php

abstract class DBResult
{
    protected $oRes;
    protected $sClass;
    public function __construct($oRes, $sClass)
    {
        $this->oRes = $oRes;
        $this->sClass = $sClass;
    }

    abstract public function fetch();
    abstract public function numRows();

    public function fetchAll()
    {
        $aRows = array();
        while ($hRow = $this->fetch()) {
            $aRows[] = $hRow;
        }
        return $aRows;
    }
}

