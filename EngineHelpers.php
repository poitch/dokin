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
require_once DOKIN_DIR.'Controller.php';
require_once DOKIN_DIR.'Config.php';

function template($path, $hData = array())
{
    global $sEngineDebug,$sControllerDebug;

    $sTemplatePath = 'app/templates/'.$path;

    if (!file_exists($sTemplatePath)) {
        throw new Exception('template '.$path.' not found');
    }

    $oController = Controller::get_instance();
    if ($oController) {
        extract($oController->get());
    }
    if (is_array($hData) && sizeof($hData)) {
        extract($hData);
    }
    include($sTemplatePath);
}

function include_content()
{
    global $_content_template;
    template($_content_template);
}

function render_partial($sTemplate,$hData)
{
    if (is_array($hData) && sizeof($hData)) {
        extract($hData);
    }

    $sTemplatePath = 'app/templates/'.$sTemplate;

    if (!file_exists($sTemplatePath)) {
        throw new Exception('template '.$path.' not found');
    }

    ob_start();
    include($sTemplatePath);
    $sContent = ob_get_contents();
    ob_end_clean();
    return $sContent;
}

function set_cookie($key, $value, $life = NULL)
{
    $aServerAddress = explode('.', $_SERVER['SERVER_NAME']);
    $sDomain = '.'.$aServerAddress[count($aServerAddress)-2].'.'.$aServerAddress[count($aServerAddress)-1];
    if ($life !== NULL) {
        $time = time() + $life;
    } else {
        $time = NULL;
    }
    setcookie($key, $value, $time, '/', $sDomain);
}

function uuid() 
{
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
}

