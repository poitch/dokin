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

abstract class SQLBaseDriver extends DBDriver
{
    public function exec($oModel)
    {
        $hTree = $oModel->getTree();
        $sClass = get_class($oModel);
        $sDatabase = $oModel->getDB();
        $sTable = $oModel->getTable();
        if ($sDatabase != null) {
            $sTable = $sDatabase . '.' . $sTable;
        }
        $sCommand = $hTree['COMMAND'];
        $hWhere = $hTree['WHERE'];
        $hSet = $hTree['SET'];
        $sIdField = $hTree['IDFIELD'];
        $aOrder = $hTree['ORDER'];

        $sQuery = '';
        if ($sCommand == SQL_COMMAND_SELECT) {
            $sQuery .= 'SELECT * FROM '.$sTable;
        } else if ($sCommand == SQL_COMMAND_UPDATE) {
            $sQuery .= 'UPDATE '.$sTable;
        } else if ($sCommand == SQL_COMMAND_DELETE) {
            $sQuery .= 'DELETE FROM '.$sTable;
        } else if ($sCommand == SQL_COMMAND_INSERT) {
            $aFields = $oModel->getFields();
            $hValues = $oModel->getValues();
            $aValues = array();
            foreach ($aFields as $i => $sField) {
                if (isset($hValues[$sField])) {
                    $hValues[$sField] = $this->escape($oModel->$sField);
                    $aValues[] = $hValues[$sField];
                } else {
                    unset($aFields[$i]);
                    unset($hValues[$sField]);
                }
            }

            $sQuery  = 'INSERT INTO '.$sTable.' ';
            $sQuery .= '('.implode(',',$aFields).') VALUES ';
            $sQuery .= '(\''.implode('\',\'',$aValues).'\')';
            $sQuery = str_replace('\'NOW()\'','NOW()',$sQuery);
        }

        if ($sCommand != SQL_COMMAND_INSERT) {
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
                    } else if (is_array($sValue)) {
                        $aEscaped = array();
                        foreach ($sValue as $sVal) {
                            $sEscaped[] = $this->escape($sVal);
                        }
                        $aWhereParts[] = $sKey.' IN (\''.implode('\',\'', $aEscaped).'\')';
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

            if ($sCommand == SQL_COMMAND_SELECT && $aOrder) {
                $sQuery .= ' ORDER BY '.implode('DESC ,', $aOrder).' DESC';
            }
        }


        $mRes = $this->query($sQuery, $sClass);

        if ($sCommand == SQL_COMMAND_INSERT) {
            $oModel->$sIdField = $this->lastId();
        }

        return $mRes;
    }

}


