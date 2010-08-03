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
require_once DOKIN_DIR.'Log.php';

class Engine
{
    private $bCompleted = false;
    private $iStart = 0;

    private function __construct()
    {
    }

    public function check_complete($data)
    {
        global $aAppConfig;
        $sParam = $aAppConfig['LOG_SLOW_REQUEST_PARAM'];
        if (($sParam && $_REQUEST[$sParam]) || !$sParam) {
            $d = microtime(true) - $this->iStart;
            $e = $aAppConfig['LOG_SLOW_TIMEOUT'] !== null ? $aAppConfig['LOG_SLOW_TIMEOUT'] : 8.1;
            if ($d > $e) {
                if ($aAppConfig['LOG_SLOW_CALLBACK'] && function_exists($aAppConfig['LOG_SLOW_CALLBACK'])) {
                    call_user_func($aAppConfig['LOG_SLOW_CALLBACK'],$this->iStart,$this->iEnd,self::$aWatches);
                } else {
                    $q = DB::get_queries();
                    $t = DB::get_total_time();
                    $sMsg = '';
                    $sMsg .= ' - '.(sizeof($q)).' DB Queries in '.round($t*1000,2).'ms';
                    if ($this->iEnd  && $this->iEnd > $this->iStart) {
                        $sMsg .= ' - method call '.round(($this->iEnd - $this->iStart)*1000,2).'ms';
                    }
                    foreach( self::$aWatches as $k => $v ) {
                        $sMsg .= ' - '.$k.' '.round(1000*$v,2).' ms';
                    }
                    _INFO('Slow Page ('.round($d*1000,2).'ms) '.$_SERVER['REQUEST_URI'].$sMsg);

                    if ($aAppConfig['TARGET'] == 'dev') {
                        foreach( $q as $h ) { 
                            if ($h['query']) {
                                _DEBUG('    '.$h['function'].' '.$h['query'].' '.round(1000*$h['time'],2).'ms');
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    function run()
    {
        global $aAppConfig;

        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;

        if (file_exists('app/lib/Helpers.php')) {
            include_once('app/lib/Helpers.php');
        }

	    if ($_SERVER['GATEWAY_INTERFACE']) {
	        $sURL = $_GET['path'];
	        $sURL = $sURL[0] == '/' ? substr($sURL, 1) : $sURL;
	    } else {
            $sURL = $_SERVER['REDIRECT_URL'];
            $sURL = substr($sURL,1);

            if ($sURL == 'dispatch.php') {
                $sURL = $_SERVER['REQUEST_URI'];
                $sURL = substr($sURL,1);
                if (strstr($sURL,'?')) {
                    $sURL = substr($sURL,0,strpos($sURL,'?'));
                }
                if (strstr($sURL,'&')) {
                    $sURL = substr($sURL,0,strpos($sURL,'&'));
                }
            }
	    }

        if (strpos($sURL,'.')) {
            $sExt = substr($sURL, strrpos($sURL, '.') + 1);
            $sURL = substr($sURL, 0, strrpos($sURL, '.'));
        }
        $sExt = $sExt ? $sExt : 'html';
        $sExt = strtolower($sExt);

        // Do we have mapping?
        $aURLComponents = explode('/',$sURL);

        $sController = $aURLComponents[0] ? $aURLComponents[0] : 'Default';
        $sController = str_replace('_',' ',$sController);
        $sController = ucwords($sController);
        $sController = str_replace(' ','',$sController);

        $sMethod     = $aURLComponents[1] ? $aURLComponents[1] : 'index';
        $sMethod     = str_replace('_',' ',$sMethod);
        $sMethod     = ucwords($sMethod);
        $sMethod     = str_replace(' ','',$sMethod);
        $sMethod     = str_replace('.','',$sMethod);
        $sMethod     = strtolower(substr($sMethod,0,1)).substr($sMethod,1);
        $sMethod     = is_numeric($sMethod) ? 'n'.$sMethod : $sMethod;

        $sControllerClass = $sController.'Controller';
        $sControllerPath = 'app/controllers/'.$sControllerClass.'.php';
        if (!file_exists($sControllerPath)) {
            // Do we have mapping? 
            $bFound = false;
            if ($aAppConfig && $aAppConfig['MAPPING'] && is_array($aAppConfig['MAPPING']) && sizeof($aAppConfig['MAPPING'])) {
                foreach ($aAppConfig['MAPPING'] as $sPattern => $sController) {
                    if (preg_match($sPattern, $sURL)) {
                        $bFound = true;
                        break;
                    }
                }
            }

            $sController = ucwords($sController);
            $sMethod = 'index';

            $sControllerClass = $sController.'Controller';
            $sControllerPath = 'app/controllers/'.$sControllerClass.'.php';
            if (!file_exists($sControllerPath)) {
                $bFound = false;
            }

            if (!$bFound) {
                if ($aAppConfig['NOT_FOUND_CONTROLLER']) {
                    $sOrigController = $sController;
                    $sController = $aAppConfig['NOT_FOUND_CONTROLLER'];
                    $sControllerClass = $sController.'Controller';
                    $sControllerPath = 'app/controllers/'.$sControllerClass.'.php';
                    if (!file_exists($sControllerPath)) {
                        $this->notFound('controller '.$sOrigController.' not found');
                    }
                } else {
                    $this->notFound('controller '.$sController.' not found');
                }
            }
        }

        $GLOBALS['controller'] = $sOrigController ? $sOrigController : $sController;
        $GLOBALS['method']     = $sMethod;

        include_once($sControllerPath);
        if ($aAppConfig['TARGET'] == 'dev') {
            _DEBUG('---> '.$sControllerClass.' '.$sMethod);
        }
        ob_start();
        $oController = new $sControllerClass();
        $__debug = ob_get_contents();
        ob_end_clean();
        if (!$oController->__early_exit) {
            if (is_array($aNoMethodCheck) && !in_array($sController,$aNoMethodCheck)
                    && !method_exists($oController,$sMethod)) {
                $this->notFound('method '.$sMethod.' not found');
            }

            $oController->set('controller',$sController);
            $oController->set('method',$sMethod);
            $oController->set('extension', $sExt);

            if ($aAppConfig['LOG_SLOW_PAGE']) {
                ob_start(array($this,check_complete));
                $this->iStart = microtime(true);
            }

            if ($bFound) {
                $args = $aURLComponents;
            } else {
                $args = array_slice($aURLComponents,2);
                if ($sOrigController) {
                    array_unshift($args, $sOrigController);
                }
            }

            $oController->$sMethod($args);
            if ($aAppConfig['LOG_SLOW_PAGE']) {
                $this->iEnd = microtime(true);
                $this->bCompleted = true;
            }
            $__debug .= ob_get_contents();
            ob_end_clean();
        }

        if (method_exists($oController,'postExec')) {
            $oController->postExec();
        }

        // TEMPLATE
        $sTemplate = $oController->getTemplate();
        $sTemplate = $sTemplate ? $sTemplate : strtolower($sController).'.php';
        $sTemplatePath = 'app/templates/'.$sTemplate;
        if (!file_exists($sTemplatePath)) {
            $this->notFound('template '.$sTemplate.' not found');
        }

        $GLOBALS['_content_template'] = $sTemplate;

        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime); 

        $oController->set('time_elapsed',$totaltime);
        $oController->set('__data',$oController->get()); 
        $oController->set('queries',DB::get_query_count());
        $oController->set('aQueries',DB::get_queries());
        $oController->set('__debug',$__debug);

        extract($oController->get());

        // LAYOUT
        $sLayout = $oController->getLayout();
        $sLayout = $sLayout ? $sLayout : strtolower($sController);
        $sLayoutPath = 'app/layouts/'.$sLayout.'.php';
        if (!file_exists($sLayoutPath)) {
            $sLayoutPath = 'app/layouts/default.php';
            if (!file_exists($sLayoutPath)) {
                // WELL WE ONLY USE THE TEMPLATE
                include_once($sTemplatePath);
            } else {
                include_once($sLayoutPath);
            }
        } else {
            include_once($sLayoutPath);
        }
    }

    function notFound($msg)
    {
        if (true) {
            header("HTTP/1.1 404 Not Found");
            header("Location: /404.html");
            _ERROR($msg);
            exit();
        } else {
            throw new Exception($msg);
        }
    }

    static private $instance = null;

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new Engine();
        }
        return self::$instance;
    }

    public static function complete()
    {
        $o = self::get_instance();
        $o->bCompleted = true;
    }


    public static $aWatches = array();
    public static function watch_start($k)
    {
        self::$aWatches[$k] = microtime(true);
    }

    public static function watch_stop($k)
    {
        self::$aWatches[$k] = microtime(true) - self::$aWatches[$k];
    }

}

