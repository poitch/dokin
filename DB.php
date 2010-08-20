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


require_once DOKIN_DIR.'dokin/Config.php';
require_once DOKIN_DIR.'dokin/DBResult.php';

class DB 
{
    protected $db;
    static protected $aQueryStack = array();
    public static $bProfiling = true;
    public static $bDeepProfiling = false;

    protected $tmpValues;
    protected $tmpBind;

    protected static $aInstances = array();

    public function __construct($name)
    {
        $oConfig = Config::get_config('DB');
        $aDatabases = $oConfig->get('databases');
        if (!$aDatabases[$name]) {
            throw new Exception('Invalid Database');
        }

        $aParams = $aDatabases[$name];

        $sHost = $aParams[0];
        if ($aParams[1]) {
            $sHost .= ':'.$aParams[1];
        }       

        $this->db = mysql_pconnect($sHost,$aParams[2],$aParams[3]);
        $retry = 0;
        while( !$this->db && ++$retry < 3 ) {
            _WARN('Could not connect to DB, attempt '.$retry);
            $this->db = mysql_pconnect($sHost,$aParams[2],$aParams[3]);
            usleep(500);
        }

        if (!$this->db) {
            throw new Exception('Database connection failed');
        }
        mysql_select_db($aParams[4]);
    }

    public static function get_instance($sName) 
    {
        if (!self::$aInstances[$sName]) {
            self::$aInstances[$sName] = new DB($sName);
        }
        return self::$aInstances[$sName];
    }

    public function query($sQuery,$hParams = array(),$bDebug = false) 
    {
        // ONLY TRY TO DO BINDING WHEN NEEDED
        if (is_array($hParams) && sizeof($hParams)) {
            $this->tmpValues = $hParams;
            $this->tmpBind = array();
            $sQuery = preg_replace_callback('/:([A-Za-z]+)/',array('self','bind'),$sQuery);

            $aKeys = array_keys($hParams);
            $aDiff = array_diff($this->tmpBind,$aKeys);
            $this->tmpBind = null;
            $this->tmpValues = null;
            if (sizeof($aDiff) > 0) {
                throw new Exception('bind failed '.$sQuery.'<br>, no value for '.print_r($aDiff,1));
            }
        }

        if ($bDebug) {
            _DEBUG($sQuery);
        }

        $a = microtime(true);
        $oResult = mysql_query($sQuery,$this->db);
        $b = microtime(true);
        if (self::$bProfiling) {
            if (self::$bDeepProfiling) {
                $t = debug_backtrace();
                $n = sizeof($t) > 6 ? 6 : sizeof($t);
                $s = '';
                for( $i = 0; $i< $n; $i++ ) {
                    $s .= $t[$i]['class'].'::'.$t[$i]['function'].':'.$t[$i]['line'].'/';
                }
                DB::$aQueryStack[] = array('query' => $sQuery, 'time' => ($b-$a), 'function' => $s);
            } else {
                DB::$aQueryStack[] = array('query' => $sQuery, 'time' => ($b-$a));
            }
            DB::$aQueryStack['total'] += ($b-$a);
        }
        if (!$oResult) {
            throw new Exception($sQuery.' ***** '.mysql_error());
        } else {
            return new DBResult($this->db,$oResult);
        }
    }

    public function close() 
    {
        mysql_close($this->db);
    }

    public static function get_last_query() 
    {
        if ($n=sizeof(DB::$aQueryStack)) {
            return DB::$aQueryStack[$n-1];
        } else {
            return null;
        }
    }

    public static function get_queries() 
    {
        $a = DB::$aQueryStack;
        unset($a['total']);
        return $a;
    }

    public static function get_query_count() 
    {
        return sizeof(DB::$aQueryStack)-1 > 0 ? sizeof(DB::$aQueryStack) : 0;
    }

    public static function get_total_time() 
    {
        return DB::$aQueryStack['total'] ? DB::$aQueryStack['total'] : 0;
    }

    private function bind($matches) 
    {
        $this->tmpBind[] = $matches[1];
        if (is_array($this->tmpValues[$matches[1]])) {
            $s = '';
            foreach ($this->tmpValues[$matches[1]] as $val) {
                $s .= '\''.mysql_real_escape_string($val).'\',';
            }
            return substr($s,0,strlen($s)-1);
        } else {
            return '\''.mysql_real_escape_string($this->tmpValues[$matches[1]]).'\'';
        }
    }

}

