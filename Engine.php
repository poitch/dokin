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
require_once DOKIN_DIR.'Log.php';

class Engine
{
    public static $aWatches = array();
    static private $instance = null;

    private function __construct()
    {
    }

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new Engine();
        }
        return self::$instance;
    }

    public function run()
    {
        global $aAppConfig;

        $iStartTime = microtime(true);

        if (file_exists('app/lib/Helpers.php')) {
            include_once('app/lib/Helpers.php');
        }

        // Retrieve the URL, remove the leading /
	    if (isset($_SERVER['GATEWAY_INTERFACE']) && isset($_GET['path'])) {
            // FCGI MODE
	        $sURL = isset($_GET['path']) ? $_GET['path'] : '/';
	        $sURL = $sURL[0] == '/' ? substr($sURL, 1) : $sURL;
	    } else {
            // MOD_PHP MODE
            $sURL = $_SERVER['REDIRECT_URL'];
            $sURL = substr($sURL, 1);

            if ($sURL == 'dispatch.php') {
                $sURL = $_SERVER['REQUEST_URI'];
                $sURL = substr($sURL, 1);

                // Remove query string
                if (strstr($sURL,'?')) {
                    $sURL = substr($sURL, 0, strpos($sURL, '?'));
                }
                if (strstr($sURL, '&')) {
                    $sURL = substr($sURL, 0, strpos($sURL, '&'));
                }
            }
	    }

        // Clean up the URL from the path where the application actually is
        $sRoot = $_SERVER['DOCUMENT_ROOT'];
        $sScript = $_SERVER['SCRIPT_FILENAME'];
        $sIgnore = substr(str_replace($sRoot, '', $sScript), 1);
        $sIgnore = dirname($sIgnore).'/';
        $sURL = str_replace($sIgnore, '', $sURL);

        // Ignore the extension
        if (strpos($sURL,'.')) {
            $sExt = substr($sURL, strrpos($sURL, '.') + 1);
            $sURL = substr($sURL, 0, strrpos($sURL, '.'));
        }
        $sExt = isset($sExt) ? $sExt : 'html';
        $sExt = strtolower($sExt);

        // Explode the URL to figure out the controller and method being called
        $aURLComponents = explode('/', $sURL);

        // Underscores and dashes are replaced by spaces, then camel case
        $sController = $aURLComponents[0] ? $aURLComponents[0] : 'Default';
        $sController = str_replace(array('_', '-'), ' ', $sController);
        $sController = ucwords($sController);
        $sController = str_replace(' ', '', $sController);

        // Underscores and dashes are replaced by spaces, periods are ignored
        // numbers will be preceded with n
        $sMethod = isset($aURLComponents[1]) ? $aURLComponents[1] : 'index';
        $sMethod = str_replace(array('_', '-'), ' ', $sMethod);
        $sMethod = ucwords($sMethod);
        $sMethod = str_replace(' ', '', $sMethod);
        $sMethod = str_replace('.', '', $sMethod);
        $sMethod = strtolower(substr($sMethod, 0, 1)).substr($sMethod,1);
        $sMethod = is_numeric($sMethod) ? 'n'.$sMethod : $sMethod;

        // Determine controller class and path
        $sControllerClass = $sController.'Controller';
        $sControllerPath = 'app/controllers/'.$sControllerClass.'.php';

        if (!file_exists($sControllerPath)) {
            // Analyze routes 
            // TODO improve routing
            if (isset($aAppConfig['MAPPING']) && is_array($aAppConfig['MAPPING'])) {
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
                $this->notFound('controller '.$sController.' not found');
            }
        }

        $GLOBALS['controller'] = $sController;
        $GLOBALS['method'] = $sMethod;

        include_once($sControllerPath);
        if ($aAppConfig['TARGET'] == 'dev') {
            _DEBUG('---> '.$sControllerClass.' '.$sMethod);
        }
        ob_start();
        $oController = new $sControllerClass();
        $__debug = ob_get_contents();
        ob_end_clean();

        if (!$oController->__early_exit) {
            if (!method_exists($oController, $sMethod)) {
                $this->notFound('method '.$sMethod.' not found');
            }

            $oController->set('controller', $sController);
            $oController->set('method', $sMethod);
            $oController->set('extension', $sExt);

            if (method_exists($oController, 'preExec')) {
                $oController->preExec();
            }

            $aArgs = array_slice($aURLComponents,2);
            $oController->$sMethod($args);
        }

        if (method_exists($oController, 'postExec')) {
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

        $iEndTime = microtime(true);
        $iTotalTime = $iEndTime - $iStartTime;

        /*
        $oController->set('time_elapsed', $iTotalTime);
        $oController->set('__data', $oController->get()); 
        $oController->set('queries', DB::get_query_count());
        $oController->set('aQueries', DB::get_queries());
        $oController->set('__debug', $__debug);
        */

        // Rendering
        if (method_exists($oController, 'preRender')) {
            $oController->preRender();
        }

        ob_start();
        $oController->_render($sTemplatePath);
        $sContent = ob_get_contents();
        ob_end_clean();

        if (method_exists($oController, 'postRender')) {
            $sContent = $oController->postRender($sContent);
        }

        print $sContent;
    }

    public function notFound($msg)
    {
        // XXX configure 404 page
        header('HTTP/1.1 404 Not Found', true, 404);
        header('Location: /404.html');
        _ERROR($msg);
        exit();
    }

    public static function watch_start($k)
    {
        self::$aWatches[$k] = microtime(true);
    }

    public static function watch_stop($k)
    {
        self::$aWatches[$k] = microtime(true) - self::$aWatches[$k];
    }
}

