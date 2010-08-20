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


function __log_message($level,$msg)
{
    global $aAppConfig;
    $aTrace = debug_backtrace();
    $trace1 = $aTrace[1];
    $trace2 = $aTrace[2];
    if ($trace1) {
        if ($trace2) {
            $sMsg = '['.$level."] [".basename($trace1['file']).':'.$trace1['line']."]\t[".$trace2['function'].'] '.$msg;
        } else {
            $sMsg = '['.$level."] [".basename($trace1['file']).':'.$trace1['line']."]\t".$msg;
        }
    } else {
        $sMsg = '['.$level.'] '.$msg;
    }

    // getcwd could be different during __destruct
    static $sPath;
    if (!$sPath) {
        $sPath = getcwd();
    }

    if (isset($_SERVER['SHELL']) && !$_SERVER['GATEWAY_INTERFACE']) {
        $sMsg = sprintf('[%-7s][%-18s:%5d][%-16s] %s',$level,basename($trace1['file']),$trace1['line'],$trace2['function'],$msg);
        print '['.date('H:i:s').']'.$sMsg."\n";
    } else {
        // We need the log server ip and port and if enabled or not
        if (false) {
            static $oServer;
            $sHost = trim(shell_exec('hostname'));
            $sApp  = $_SERVER['SERVER_NAME'];
            $sFile = basename($trace1['file']);
            $sLine = $trace1['line'];
            $sFunc = $trace2['function'];
            $sFmt  = '[%s:%d][%s] %s (%s)(%s)';
            $sMsg  = sprintf($sFmt, $sFile, $sLine, $sFunc, $msg, $sApp, $sHost);

            if (!$oServer) {
                $sServer = $aAppConfig['LOG_SERVER'];
                if (strpos($sServer, ':') !== false) {
                    $sServer = substr($sServer, 0, strpos($sServer, ':'));
                    $iPort = (int)substr($sServer, strpos($sServer, ':'));
                } else {
                    $iPort = 8888;
                }
                $oServer = fsockopen($sServer, $iPort);
            }
            if ($oServer) {
                $hData = array();

                $hData['webapp']      = $sApp;
                $hData['hostname']    = $sHost;
                $hData['level']       = $level;
                $hData['timestamp']   = time();
                $hData['timestamp.u'] = microtime(true);
                $hData['file']        = $sFile;
                $hData['line']        = $sLine;
                $hData['function']    = $sFunc;
                $hData['message']     = $msg;
                $hData['formatted']   = $sMsg;
                $hData['agent']       = $_SERVER['HTTP_USER_AGENT'];
                fwrite($oServer, json_encode($hData)."\n");
            }
        }
        error_log($sMsg);
    }
}

function _DEBUG($msg)
{
    global $aAppConfig;
    if ($aAppConfig['TARGET'] == 'dev' || $_SERVER['SHELL']) {
        __log_message('DEBUG',$msg);
    }
}

function _INFO($msg)
{
    __log_message('INFO',$msg);
}

function _WARN($msg)
{
    __log_message('WARN',$msg);
}

function _WARNING($msg)
{
    __log_message('WARN',$msg);
}

function _ERR($msg)
{
    __log_message('ERROR',$msg);
}

function _ERROR($msg)
{
    __log_message('ERROR',$msg);
}

function _FATAL($msg)
{
    __log_message('FATAL',$msg);
}

