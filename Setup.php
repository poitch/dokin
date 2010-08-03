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


define('DOKIN_DIR','./dokin/');
define('DOKIN_PLUGINS_DIR','./dokin/plugins/');
define('APP_DIR','./app/');
define('CONFIG_DIR','./app/config/');
define('CONTROLLERS_DIR','./app/controllers/');
define('CONTROLLER_DIR','./app/controllers/');
define('MODELS_DIR','./app/models/');
define('MODEL_DIR','./app/models/');
define('TEMPLATES_DIR','./app/templates/');
define('TEMPLATE_DIR','./app/templates/');
define('LIB_DIR','./app/lib/');

require_once DOKIN_DIR.'Engine.php';
require_once DOKIN_DIR.'EngineHelpers.php';
require_once DOKIN_DIR.'DB.php';
require_once DOKIN_DIR.'Log.php';

function exception_handler($e)
{
    global $aAppConfig;
    $n = ob_get_level();
    for ($i = 1; $i < $n; $i++)
    {
        ob_end_clean();
    }
    if (!empty($aAppConfig['EXCEPTION_TEMPLATE']) && file_exists('app/templates/'.$aAppConfig['EXCEPTION_TEMPLATE'])) {
        if(file_exists('app/templates/'.$aAppConfig['EXCEPTION_TEMPLATE'])) {
            include('app/templates/'.$aAppConfig['EXCEPTION_TEMPLATE']);
        } else {
            $msg  = '<html><head><title>Unexpected Error</title></head><body>';
            print "$msg";
        } 
    } else {
        $msg  = '<html><head><title>Unexpected Error</title></head><body>';
        print "$msg";
    }
    $hFatal = array();
    $hFatal['exception'] = $e;
    $hFatal['request']   = $_REQUEST;
    $hFatal['referer']   = $_SERVER['HTTP_REFERER'];
    $hFatal['request']   = $_SERVER['REQUEST_URI'];
    _FATAL(print_r($hFatal,1));
}

function __autoload($sClass)
{
    $aDirs = array(DOKIN_DIR, DOKIN_PLUGINS_DIR, APP_DIR, CONFIG_DIR, CONTROLLERS_DIR, MODELS_DIR, LIB_DIR,);
    foreach ($aDirs as $sDir) {
        if (file_exists($sDir.$sClass.'.php')) {
            require_once($sDir.$sClass.'.php');
            return;
        }
    }
}

set_exception_handler('exception_handler');

