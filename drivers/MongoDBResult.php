<?php
require_once DOKIN_DIR.'DBResult.php';

class MongoDBResult extends DBResult
{
    public function fetch()
    {
        $hRow = $this->oRes->getNext();
        if ($this->sClass && $hRow) {
            if (is_object($hRow['_id'])) {
                $hRow['_id'] = $hRow['_id']->__toString();
            }
            $sClass = $this->sClass;
            return Model::get_instance($sClass, $hRow);
        }
        return $hRow;
    }

    public function numRows()
    {
        return $this->oRes->count();
    }
}
