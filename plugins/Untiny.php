<?php

class Untiny
{
    public static $bDebug = false;
    public static function url($sURL, $iDepth = 0, $bFollow = false)
    {
        if (self::$bDebug) _DEBUG('-> '.$sURL.' (attempt #'.$iDepth.')');
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Connection: close'));
        curl_setopt($oCurl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en) AppleWebKit/419 (KHTML, like Gecko) Safari/419.3');
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);
        curl_setopt($oCurl, CURLOPT_VERBOSE, 0);
        if ($bFollow) {
            curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, 1);
        }

        curl_setopt($oCurl, CURLOPT_TIMEOUT, 10 );

        $sResult = curl_exec($oCurl);
        if (!$sResult) {
            if (self::$bDebug) _DEBUG('Timed out');
            return null;
        }
        $iCode = curl_getinfo($oCurl,  CURLINFO_HTTP_CODE);

        $aHeaders    = array();
        $iHeaderSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        $sHeaders    = substr($sResult, 0, $iHeaderSize - 4);
        $aHeaders    = explode("\r\n",$sHeaders);
        $sResult     = substr($sResult, $iHeaderSize);

        curl_close($oCurl);
        if (self::$bDebug) _DEBUG('<- '.$iCode);
        if ($iCode == 302 || $iCode == 301) {
            foreach ($aHeaders as $sLine) {
                $aParts = explode(':', $sLine);
                if (strtolower($aParts[0]) == 'location') {
                    $sNew = trim(substr($sLine, strpos($sLine, ':') + 1));
                    if ($iDepth < 5) {
                        return Untiny::url($sNew, $iDepth + 1);
                    } else {
                        return array('url' => $sNew);
                    }
                }
            }
        } else {
            foreach ($aHeaders as $sLine) {
                $aParts = explode(':', $sLine);
                if (strtolower($aParts[0]) == 'content-type') {
                    if (stripos($sLine, 'text/html') !== false) {
                        // Find title in HTML
                        if (stripos($sResult, '</title>') !== false) {
                            $sPref = substr($sResult, 0, stripos($sResult, '</title>'));
                            $sTitle = trim(substr($sPref, strripos($sResult, '<title>') + 7));
                        }
                        if (stripos($sResult, '<meta name="description" content="') !== false) {
                            $sPref = substr($sResult, stripos($sResult, '<meta name="description" content="') + 34);
                            if (strpos($sPref, 'digg_url') !== false) {
                                $sPref = substr($sPref, strpos($sPref, ';') + 1);
                            }
                            if (strpos($sPref, 'digg_skin') !== false) {
                                $sPref = substr($sPref, strpos($sPref, ';') + 1);
                            }
                            $sPref = trim($sPref);
                            $sDescription = substr($sPref, 0, strpos($sPref, '"'));
                        }
                    } 
                    $sMime = trim(substr($sLine, strpos($sLine, ':') +1));
                    break;
                }
            }

            $hResult = array();
            $hResult['url'] = $sURL;
            if (!empty($sTitle)) {
                $hResult['title'] = $sTitle;
            }
            if (!empty($sDescription)) {
                $hResult['description'] = $sDescription;
            }
            if (!empty($sMime)) {
                $hResult['mime'] = $sMime;
            }
            return $hResult;
        }
    }

}

