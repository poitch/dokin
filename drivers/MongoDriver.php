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

    public function query($sQuery, $sClass = null)
    {
    }

    public function escape($sString)
    {
    }

    public function lastId()
    {
    }
}

