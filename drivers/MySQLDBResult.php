<?php

require_once DOKIN_DIR.'DBResult.php';

class MySQLDBResult extends DBResult
{
    public function fetch()
    {
        $hRow = mysql_fetch_assoc($this->oRes);
        if ($this->sClass && $hRow) {
            return Model::get_instance($this->sClass, $hRow);
        }
        return $hRow;
    }

    public function numRows()
    {
        return mysql_num_rows($this->oRes);
    }
}

