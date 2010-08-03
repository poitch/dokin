<?php
/**
 *****************************************************************************
 ** Copyright (c) 2007-2009 Jerome Poichet <jerome@frencaze.com>
 **
 ** This software is supplied to you by Jerome Poichet in consideration of 
 ** your agreement to the following terms, and your use, installation, 
 ** modification or redistribution of this software constitutes acceptance of 
 ** these terms. If you do not agree with these terms, please do not use, 
 ** install, modify or redistribute this software.
 **
 ** In consideration of your agreement to abide by the following terms, and 
 ** subject to these terms, Jerome Poichet grants you a personal, non-exclusive
 ** license, to use, reproduce, modify and redistribute the software, with or 
 ** without modifications, in source and/or binary forms; provided that if you
 ** redistribute the software in its entirety and without modifications, you 
 ** must retain this notice and the following text and disclaimers in all such 
 ** redistributions of the software, and that in all cases attribution of 
 ** Jerome Poichet as the original author of the source code shall be included
 ** in all such resulting software products or distributions.
 **
 ** Neither the name, trademarks, service marks or logos of Jerome Poichet may
 ** be used to endorse or promote products derived from the software without 
 ** specific prior written permission from Jerome Poichet. Except as expressly
 ** stated in this notice, no other rights or licenses, express or implied, are
 ** granted by Jerome Poichet herein, including but not limited to any patent
 ** rights that may be infringed by your derivative works or by other works in
 ** which the software may be incorporated.
 ** 
 ** The software is provided by Jerome Poichet on an "AS IS" basis. 
 ** JEROME POICHET MAKES NO WARRANTIES, EXPRESS OR IMPLIED, INCLUDING WITHOUT 
 ** LIMITATION THE IMPLIED WARRANTIES OF NON-INFRINGEMENT, MERCHANTABILITY AND 
 ** FITNESS FOR A PARTICULAR PURPOSE, REGARDING THE SOFTWARE OR ITS USE AND 
 ** OPERATION ALONE OR IN COMBINATION WITH YOUR PRODUCTS.
 ** 
 ** IN NO EVENT SHALL JEROME POICHET BE LIABLE FOR ANY SPECIAL, INDIRECT, 
 ** INCIDENTAL OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, 
 ** PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 ** OR BUSINESS INTERRUPTION) ARISING IN ANY WAY OUT OF THE USE, REPRODUCTION,
 ** MODIFICATION AND/OR DISTRIBUTION OF THE SOFTWARE, HOWEVER CAUSED AND 
 ** WHETHER UNDER THEORY OF CONTRACT, TORT (INCLUDING NEGLIGENCE), STRICT 
 ** LIABILITY OR OTHERWISE, EVEN IF JEROME POICHET HAS BEEN ADVISED OF THE 
 ** POSSIBILITY OF SUCH DAMAGE.
 *****************************************************************************
 **/
require_once DOKIN_DIR.'DB.php';

class Model 
{
    protected $name;
    protected $fields = array();
    protected $idField;
    protected $values = array();
    protected $loaded_values = array();

    protected $extraValues = array();

    private static $__debug_cache = false;

    public $bDirty = false;

    public function __construct($m = null) 
    {
        if (is_scalar($m)) {
            $this->values[$this->idField] = $m;
            $oDB = DB::get_instance('slave');
            $this->load($oDB);
            Model::__cache_add($this);
        } else if (is_array($m)) {
            foreach ($m as $k => $v) {
                if (in_array($k,$this->fields)) {
                    $this->values[$k]        = $v;
                    $this->loaded_values[$k] = $v;
                } else {
                    $this->extraValues[$k]   = $v;
                }
            }
            Model::__cache_add($this);

        } else if (is_object($m)) {
            foreach ($this->fields as $sField) {
                if ($m->$sField) {
                    $this->values[$sField]        = $m->$sField;
                    $this->loaded_values[$sField] = $m[$sField];
                }
            } 
        }
    }

    public function get($key) 
    {
        if (in_array($key,$this->fields)) {
            return $this->values[$key];
        } else {
            return $this->extraValues[$key];
        }
    }

    public function set($key,$value) 
    {
        if (in_array($key,$this->fields)) {
            $this->values[$key] = $value;
            if ($this->loaded_values[$key] !== $this->values[$key]) {
                $this->bDirty = true;
            }
        } else {
            $this->extraValues[$key] = $value;
        }
    }

    public function remove($key)
    {
        if (in_array($key,$this->fields)) {
            unset($this->values[$key]);
        } else {
            unset($this->extraValues[$key]);
        }
    }

    public function load($oDB = null) 
    {
        if ($oDB === null) {
            $oDB = DB::get_instance('slave');
        }

        $sQuery  = 'SELECT '.implode(',',$this->fields).' FROM '.$this->name;
        $sQuery .= ' WHERE '.$this->idField.' = \''.mysql_real_escape_string($this->values[$this->idField]).'\'';
        $oResult = $oDB->query($sQuery);
        if ($oResult->numRows() == 1) {
            $this->values = $oResult->fetch();
            $this->loaded_values = $this->values;
        } else {
            $sMsg = $this->values[$this->idField].' not found in '.$this->name;
            _ERROR($sMsg);
            return null;
            //throw new Exception($sMsg);
        }
    }

    public function insert($oDB = null) 
    {
        if ($oDB === null) {
            $oDB = DB::get_instance('master');
        }
        $hFields = array();
        $hValues = array();
        foreach ($this->fields as $field) {
            if (isset($this->values[$field])) {
                $hFields[] = $field;
                $hValues[] = mysql_real_escape_string($this->values[$field]);
            } else {
                unset($this->values[$field]);
            }
        }

        $sQuery  = 'INSERT INTO '.$this->name.' ';
        $sQuery .= '('.implode(',',$hFields).') VALUES ';
        $sQuery .= '(\''.implode('\',\'',$hValues).'\')';
        $sQuery = str_replace('\'NOW()\'','NOW()',$sQuery);

        try {
            $oDB->query($sQuery);
        } catch( Exception $e ) {
            _ERROR($e->getMessage());
            throw $e;
        }

        $this->values[$this->idField] = mysql_insert_id();
        if ($this->values[$this->idField]) {
            $this->loaded_values = $this->values;
        }

        Model::__cache_add($this);

        return $this->values[$this->idField];
    }

    public function update($oDB = null ) 
    {
        if ($oDB === null) {
            $oDB = DB::get_instance('master');
        }
        $hValues = array();
        foreach ($this->fields as $key) {
            if ($this->loaded_values[$key] !== $this->values[$key]) {
                if ($this->values[$key] === 'NOW()') {
                    $hValues[] = $key.'= NOW()';
                } else if ($this->values[$key] === 'INCREMENT()') {
                    $hValues[] = $key.'='.$key.'+1';
                } else if ($this->values[$key] === NULL) {
                    $hValues[] = $key.'= NULL';
                } else {
                    $hValues[] = $key.'=\''.mysql_real_escape_string($this->values[$key]).'\'';
                }
            }
        }

        if (!sizeof($hValues)) {
            // WARNING MAYBE?
            _WARN('nothing to update in '.get_class());
            return false;
        }

        $sQuery  = 'UPDATE '.$this->name.' SET ';
        $sQuery .= implode(',',$hValues);
        $sQuery .= ' WHERE '.$this->idField.' = \''.mysql_real_escape_string($this->loaded_values[$this->idField]).'\'';

        try {
            $oDB->query($sQuery);
        } catch ( Exception $e ) {
            _ERROR($e->getMessage());
            throw $e;
        }

        Model::__cache_update($this);

        return true;
    }

    public function delete($oDB = null) 
    {
        if ($oDB === null) {
            $oDB = DB::get_instance('master');
        }
        $sQuery  = 'DELETE FROM '.$this->name.' WHERE ';
        $sQuery .= $this->idField.' =\''.mysql_real_escape_string($this->loaded_values[$this->idField]).'\'';
        try {
            $oDB->query($sQuery);
        } catch ( Exception $e ) {
            _ERROR($e->getMessage());
            throw $e;
        }

        Model::__cache_delete($this);
    }

    static public function find($sClass, $aWhere = array(), $aOrder = array(), $iOffset = null, $iLimit = null, $oDB = null)
    {
        if (!sizeof($aWhere)) {
            return self::fetch_all($sClass,$iOffset,$iLimit,$oDB);
        }

        if (!Model::$__fields_cache[$sClass]['object']) {
            Model::__get_fields($sClass);
        }
        $object = Model::$__fields_cache[$sClass]['object'];

        if ($oDB === null) {
            $oDB = DB::get_instance('slave');
        }

        $sQuery = 'SELECT * FROM '.$object->name;

        $aParts = array();
        foreach ($aWhere as $sKey => $sValue) {
           if ($sValue === null) {
                $aParts[] = $sKey . ' IS NULL ';
            } else if (is_array($sValue)) {
                $aIns = array();
                foreach ($sValue as $sValue2) {
                    $aIns[] .= '\''.mysql_real_escape_string($sValue2).'\'';
                }
                if (sizeof($aIns)) {
                    $aParts[] = $sKey .= ' IN ('.implode(',', $aIns).')';
                }
            } else {
                $aParts[] = $sKey .' = \''.mysql_real_escape_string($sValue).'\'';
            }
        }
        if (sizeof($aParts)) {
            $sQuery .= ' WHERE '.implode(' AND ',$aParts);
        }

        if ($aOrder && is_array($aOrder) && sizeof($aOrder)) {
            $sQuery .= ' ORDER BY ';
            foreach ($aOrder as $sKey => $sSort) {
                if (is_numeric($sKey)) {
                    $aOrderParts[] = $sSort;
                } else {
                    $aOrderParts[] = $sKey.' '.$sSort;
                }
            }
            $sQuery .= implode(',', $aOrderParts);
        }

        if ($iLimit != null && is_numeric($iLimit)) {
            if ($iOffset != null && is_numeric($iOffset) ) {
                $sQuery .= ' LIMIT '.$iOffset.','.$iLimit;
            } else {
                $sQuery .= ' LIMIT '.$iLimit;
            }
        }

        try {
            $oResult = $oDB->query($sQuery);
        } catch ( Exception $e ) {
            _ERROR($e->getMessage());
            throw $e;
        }

        $aResults = array();
        while ($hResult = $oResult->fetch()) {
            $aResults[] = new $sClass($hResult);
        }

        return $aResults;

    }

    static public function fetch_all($sClass, $iOffset = null, $iLimit = null, $oDB = null) 
    {
        if (!Model::$__fields_cache[$sClass]['object']) {
            Model::__get_fields($sClass);
        }
        $object = Model::$__fields_cache[$sClass]['object'];

        if ($oDB === null) {
            $oDB = DB::get_instance('slave');
        }

        $sQuery = 'SELECT * FROM '.$object->name;

        if ($iLimit != null && is_numeric($iLimit)) {
            if ($iOffset != null && is_numeric($iOffset)) {
                $sQuery .= ' LIMIT '.$iOffset.','.$iLimit;
            } else {
                $sQuery .= ' LIMIT '.$iLimit;
            }
        }


        try {
            $oResult = $oDB->query($sQuery);
        } catch ( Exception $e ) {
            _ERROR($e->getMessage());
            throw $e;
        }

        $aResults = array();
        while ($hResult = $oResult->fetch()) {
            $aResults[] = new $sClass($hResult);
        }

        return $aResults;
    }

    public function toHash() 
    {
        $hRes = array();
        foreach ($this->fields as $field) {
            $hRes[$field] = $this->values[$field];
            if ($this->extraValues[$field.'Object']) {
                $hRes[$field] = $this->extraValues[$field.'Object']->toHash();
            }
        }
        foreach ($this->extraValues as $key => $val) {
            if (is_object($val) && is_subclass_of($val,'Model')) {
                $hRes[$key] = $val->toHash();
            } else if (!is_object($val)) {
                $hRes[$key] = $val;
            }
        }
        return $hRes;
    }

    public function getFields() 
    {
        return $this->fields;
    }

    public function getIdField() 
    {
        return $this->idField;
    }

    protected static $__object_cache;
    public static $bCaching = true;

    public static function get_instance($sClass,$iId) 
    {
        if (self::$bCaching) {
            if (!Model::$__object_cache[$sClass][$iId]) {
                if (Model::$__debug_cache) {
                    _DEBUG('[C] creating '.$sClass.' '.$iId);
                }
                new $sClass($iId);
            } else if (Model::$__debug_cache) {
                _DEBUG('[C] hit '.$sClass.' '.$iId);
            }
            return Model::$__object_cache[$sClass][$iId];
        } else {
            return new $sClass($iId);
        }
    }

    final private static function __cache_add($oObject) 
    {
        if (self::$bCaching) {
            $sClass = get_class($oObject);
            if (!Model::$__object_cache[$sClass][$oObject->get('id')]) {
                if (Model::$__debug_cache) {
                    _DEBUG('[C] adding '.$sClass.' '.$oObject->get('id'));
                }
                Model::$__object_cache[$sClass][$oObject->get('id')] = $oObject;
            }
        }
    }

    final private static function __cache_update($oObject) 
    {
        if (self::$bCaching) {
            $sClass = get_class($oObject);
            if (Model::$__debug_cache) {
                _DEBUG('[C] updating '.$sClass.' '.$oObject->get('id'));
            }
            Model::$__object_cache[$sClass][$oObject->get('id')] = $oObject;
        }
    }

    final private static function __cache_delete($oObject) 
    {
        if (self::$bCaching) {
            $sClass = get_class($oObject);
            if (Model::$__debug_cache) {
                _DEBUG('[C] deleting '.$sClass.' '.$oObject->get('id'));
            }
            unset(Model::$__object_cache[$sClass][$oObject->get('id')]);
        }
    }


    public static $__fields_cache;
    public static function __get_fields($sClass) 
    {
        if (!Model::$__fields_cache[$sClass]['fields']) {
            $object = new $sClass();
            Model::$__fields_cache[$sClass]['object']   = $object;
            Model::$__fields_cache[$sClass]['fields']   = $object->getFields();
            Model::$__fields_cache[$sClass]['id_field'] = $object->getIdField();
        }
    }

    public static function get_fields($sClass) 
    {
        if (!Model::$__fields_cache[$sClass]['fields']) {
            Model::__get_fields($sClass);
        }
        return Model::$__fields_cache[$sClass]['fields'];
    }

    public static function get_id_field($sClass) 
    {
        if (!Model::$__fields_cache[$sClass]['id_field']) {
            Model::__get_fields($sClass);
        }
        return Model::$__fields_cache[$sClass]['id_field'];
    }


    public function __get($name) 
    {
        if (isset($this->values[$name])) {
            return $this->values[$name];
        } else if (isset($this->extraValues[$name])) {
            return $this->extraValues[$name];
        } else {
            return null;
        }
    }

    public function __set($name,$value) 
    {
        if (in_array($name,$this->fields)) {
            $this->values[$name]        = $value;
            if ($this->loaded_values[$key] !== $this->values[$key]) {
                $this->bDirty = true;
            }
        } else {
            $this->extraValues[$name]   = $value;
        }
    }

    public function __call($name,$args) 
    {
        if (strtolower(substr($name,0,2)) == 'by') {
            $name = substr($name,2);
            if (strlen($name)) {
                $parts = explode('And',$name);
                $aWhere = array();
                foreach ($parts as $i => $k) {
                    $key = strtolower($k[0]).substr($k,1);
                    $aWhere[$key] = $args[$i];
                }
                if ($i < sizeof($args) - 1) {
                    $iOffset = $args[$i+1];
                    $iLimit = $args[$i+2];
                    $oDB = $args[$i+3];
                }
                $sClass = get_class($this);
                return self::find($sClass,$aWhere,null,$iOffset,$iLimit,$oDB);
            } else {
                if (sizeof($args)) {
                    $iOffset = $args[0];
                    $iLimit = $args[1];
                    $oDB = $args[2];
                }
                return self::fetch_all($sClass,$iOffset,$iLimit,$oDB);
            }
        }
        die ('<div><b>Fatal Error:</b> unknown method ' . $name . ' in ' . __CLASS__ . '.</div>');

    }
}

