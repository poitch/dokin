<?php
require_once DOKIN_DIR.'DBResult.php';

class SQLiteDBResult extends DBResult
{
    public function fetch()
    {
        $hRow = sqlite_fetch_array($this->oRes);
        if ($this->sClass) {
            $sClass = $this->sClass;
            return Model::get_instance($sClass, $hRow);
        }
        return $hRow;
    }

    public function numRows()
    {
        return sqlite_num_rows($this->oRes);
    }
}

