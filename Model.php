<?php
/**
 *****************************************************************************
 ** Copyright (c) 2007-2010 Jerome Poichet <jerome@frencaze.com>
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


require_once DOKIN_DIR.'DBDriver.php';

define('SQL_COMMAND_NONE', 'NONE');
define('SQL_COMMAND_SELECT', 'SELECT');
define('SQL_COMMAND_DELETE', 'DELETE');
define('SQL_COMMAND_UPDATE', 'UPDATE');

class Model
{
    protected $sDatabase;
    protected $sTable;
    protected $sIdField;
    protected $aFields = array();

    protected $hValues = array();
    protected $hLoadedValues = array();
    protected $hExtraValues = array();

    protected $sCommand;
    protected $aAnd = null;
    protected $aOr = null;
    protected $aSet = null;

    public function __construct($mObj = null)
    {
        if (is_scalar($mObj)) {
            // TODO load using idfield

        } else if (is_array($mObj)) {
            foreach ($mObj as $sKey => $sValue) {
                if (in_array($sKey, $this->aFields)) {
                    $this->hValues[$sKey] = $sValue;
                    $this->hLoadedValues[$sKey] = $sValue;
                } else {
                    $this->hExtraValues[$sKey] = $sValue;
                }
            }
        }
    }

    public function getFields()
    {
        return $this->aFields;
    }

    public function getValues()
    {
        return $this->hValues;
    }


    public function _get($sKey) 
    {
        if (in_array($sKey, $this->aFields)) {
            return $this->hValues[$sKey];
        } else {
            return $this->hExtraValues[$sKey];
        }
    }

    public function _set($sKey, $sValue) 
    {
        if (in_array($sKey, $this->aFields)) {
            $this->hValues[$sKey] = $sValue;
            if ($this->hLoadedValues[$sKey] !== $this->hValues[$sKey]) {
            }
        } else {
            // Do not set as extra value if an object's attribute
            $oReflect = new ReflectionClass($this);
            $aProps = $oReflect->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);
            foreach ($aProps as $hProp) {
                if ($hProp->name == $sKey) {
                    trigger_error($sKey.' is not a public member', E_USER_ERROR);
                }
            }
            print_r($aProps);

            $this->hExtraValues[$sKey] = $sValue;
        }
    }

    public function remove($sKey)
    {
        unset($this->hValues[$sKey]);
        //unset($this->hLoadedValues[$sKey]);
        unset($this->hExtraValues[$sKey]);
    }

    public function __get($sName) 
    {
        return $this->_get($sName);
    }

    public function __set($sName, $sValue) 
    {
        return $this->_set($sName, $sValue);
    }



    public static function get_instance($sClass, $mObj = null, $oDriver = null)
    {
        $oTmp = new $sClass($mObj);

        $sIdField = $oTmp->sIdField;
        if (!$oTmp->$sIdField && $oDriver !== null) {
            $mRes = $oTmp->select($sClass)->where($sIdField, $mObj)->exec($oDriver);
            $oTmp = $mRes->fetch();
        }

        return $oTmp;
    }

    public function getDB()
    {
        return $this->sDatabase;
    }


    public function getTable()
    {
        return $this->sTable;
    }

    public function getTree()
    {
        return array(
            'COMMAND' => $this->sCommand, 
            'WHERE' => $this->aAnd,
            'SET' => $this->aSet,
            'IDFIELD' => $this->sIdField,
        );
    }

    public static function select($sClass)
    {
        $oObj = self::get_instance($sClass);
        $oObj->sCommand = SQL_COMMAND_SELECT;
        return $oObj;
    }

    public function insert($mDriver = null)
    {
        $this->sCommand = SQL_COMMAND_INSERT;
        if ($mDriver !== null && is_subclass_of($mDriver, 'DBDriver')) {
            return $this->exec($mDriver);
        }
        return $this;
    }
    
    public function delete($mClass = null)
    {
        if (!$this || !is_subclass_of($this, 'Model')) {
            if ($mClass === null) {
                trigger_error('Provide class name', E_USER_ERROR);
            }
            $oObj = self::get_instance($mClass);
            $oObj->sCommand = SQL_COMMAND_DELETE;
        } else {
            $oObj = $this;
            $oObj->sCommand = SQL_COMMAND_DELETE;
            // for the id_field = id
            $mId = $oObj->sIdField;
            $oObj->where($mId, $oObj->$mId);
            if ($mClass !== null && is_subclass_of($mClass, 'DBDriver')) {
                return $oObj->exec($mClass);
            }
        }
        return $oObj;
    }

    public function update($mClass = null)
    {
        // TODO
        //build the set tree here?
        if ($this && is_subclass_of($this, 'Model')) {
            if (!is_array($this->aSet)) {
                foreach ($this->aFields as $sKey) {
                    if ($this->hValues[$sKey] !== $this->hLoadedValues[$sKey]) {
                        $this->aSet[$sKey] = $this->hValues[$sKey];
                    }
                }
            }
        }

        if (!$this || !is_subclass_of($this, 'Model')) {
            $oObj = self::get_instance($mClass);
            $oObj->sCommand = SQL_COMMAND_UPDATE;
        } else {
            $oObj = $this;
            $oObj->sCommand = SQL_COMMAND_UPDATE;
            // for the id_field = id
            $mId = $oObj->sIdField;
            $oObj->where($mId, $oObj->$mId);
            if ($mClass !== null && is_subclass_of($mClass, 'DBDriver')) {
                return $oObj->exec($mClass);
            }
        }
        return $oObj;
    }

    public function set($mKey, $mValue = null)
    {
        if (!is_array($mKey)) {
            $mKey = array($mKey => $mValue);
        }
        $this->aSet = is_array($this->aSet) ? $this->aSet : array();
        $this->aSet = array_merge($this->aSet, $mKey);
        return $this;
    }

    public function where($mKey, $mValue = null)
    {
        return $this->andWhere($mKey, $mValue);
    }

    public function andWhere($mKey, $mValue = null)
    {
        if (!is_array($mKey)) {
            $mKey = array($mKey => $mValue);
        }
        $this->aAnd = is_array($this->aAnd) ? $this->aAnd : array();
        $this->aAnd = array_merge($this->aAnd, $mKey);
        return $this;
    }

    public function orWhere()
    {
        return $this;
    }

    public function like()
    {
        return $this;
    }

    public function limit()
    {
        return $this;
    }

    public function having()
    {
        return $this;
    }

    public function exec($oDriver = null)
    {
        if (!$oDriver) {
            throw new Exception('Provide a DB Driver');
        }
        return $oDriver->exec($this);
    }

    public function toHash() 
    {
        $hRes = array();
        foreach ($this->aFields as $sField) {
            if ($sField == $this->sIdField && !isset($this->hValues[$sField])) {
            } else {
                $hRes[$sField] = $this->hValues[$sField];
            }
        }
        foreach ($this->hExtraValues as $sKey => $mVal) {
            if (is_object($mVal) && is_subclass_of($mVal,'Model')) {
                $hRes[$sKey] = $mVal->toHash();
            } else if (!is_object($mVal)) {
                $hRes[$sKey] = $mVal;
            }
        }
        return $hRes;
    }


}

