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

class S3 
{
    private $sKey        = '';
    private $sSecret     = '';
    private $sServer     = 's3.amazonaws.com';
    private $sDate       = null;
    private $sError      = null;

    public function __construct($key,$secret) 
    {
        $this->sKey = $key;
        $this->sSecret = $secret;
    }

    public function getAuthFor($sPath)
    {
        $sPath = $sPath[0] == '/' ? $sPath : '/'.$sPath;
        $iExpires = time() + 5 * 60;

        $sToSign = "GET\n\n\n$iExpires\n$sPath";
        $oCrypt = new HMAC($this->sSecret, 'sha1');
        $sSignature = $oCrypt->hash($sToSign);
        $sSignature = $this->base64($sSignature);

        $sAuth  = '?';
        $sAuth .= 'AWSAccessKeyId=' . $this->sKey. '&';
        $sAuth .= 'Signature='.urlencode($sSignature).'&';
        $sAuth .= 'Expires='.$iExpires;
                                                                                                            return $sAuth;
    }

    public function bucketsGet() 
    {
        $hRequest = array();
        $hRequest['verb'] = 'GET';
        $hRequest['resource'] = '/';

        $hResult = $this->sendRequest($hRequest);

        $oXML = simplexml_load_string($hResult['body']);
        if ($oXML->Code) {
            throw new Exception((string)$oXML->Message);
        }

        $aBuckets = array();
        foreach ($oXML->Buckets->Bucket as $oBucket) {
            $hBucket = array();
            $hBucket['name'] = (string)$oBucket->Name;
            $hBucket['creationDate'] = (string)$oBucket->CreationDate;
            $aBuckets[] = $hBucket;
        }
        return $aBuckets;
    }

    public function bucketExists($sBucket)
    {
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] = 'HEAD';
        $hRequest['resource'] = $sBucket;
        $hRequest['headers']['max-keys'] = 0;
        $hRequest['headers']['Connection'] = 'Close';

        $hResult = $this->sendRequest($hRequest);

        return $this->positive($hResult);
    }

    public function bucketCreate($sBucket) 
    {
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] =  'PUT';
        $hRequest['resource'] = $sBucket;
        $sBody = null;
        if ($bEurope) {
            $sBody = '<CreateBucketConfiguration><LocationConstraint>EU</LocationConstraint></CreateBucketConfiguration>';
        }

        $hResult = $this->sendRequest($hRequest, $sBody);
        print_r($hResult);

        return $this->positive($hResult);
    }

    public function bucketSetRequestPayment($sBucket, $bRequester = true)
    {
        // FIXME same issue, we need host: + /?requestPayment
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] =  'PUT';
        $hRequest['resource'] = $sBucket.'?requestPayment';
        $sBody = '<RequestPaymentConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">';
        if ($bRequester) {
            $sBody .= '<Payer>Requester</Payer>';
        } else {
            $sBody .= '<Payer>BucketOwner</Payer>';
        }
        $sBody .= '</RequestPaymentConfiguration>';

        $hResult = $this->sendRequest($hRequest, $sBody);

        return $this->positive($hResult);
    }

    public function bucketDelete($bucket) 
    {
        $hRequest = array();
        $hRequest['verb']     =  'DELETE';
        $hRequest['resource'] = '/'.$bucket;
        $hResult = $this->sendRequest($hRequest);
        return $this->positive($hResult);
    }

    public function bucketGet($bucket, $maxKeys = 1000, $prefix = null, $delim = null, $marker = null, $recursive = true) 
    {
        return $this->bucketGetContent($bucket, $maxKeys, $prefix, $delim, $marker, $recursive);
    }

    public function bucketGetContent($bucket, $maxKeys = 1000, $prefix = null, $delim = null, $marker = null, $recursive = true) 
    {
        $aContent = array();
        do {
            $hRequest = array();
            $hRequest['verb']     =  'GET';
            $hRequest['resource'] = '/'.$bucket;

            if ($prefix[0] == '/') {
                $prefix[0] = '';
            }
            $hParams = array();
            $hParams['prefix']    = trim($prefix);
            $hParams['marker']    = $sLast ? $sLast : $marker;
            $hParams['delimiter'] = $delim;
            $hParams['max-keys']  = $maxKeys;

            $hResult = $this->sendRequest($hRequest,$hParams);

            $oXML = simplexml_load_string($hResult['body']);
            if ($oXML->Code) {
                throw new Exception((string)$oXML->Message);
            }

            if ($recursive) {
                $bTruncated = ((string)$oXML->IsTruncated == 'true') ? true : false;
            } else {
                $bTruncated = false;
            }

            foreach( $oXML->Contents as $oContent ) {
                $hObject = array();
                $sLast = $hObject['name'] = (string)$oContent->Key;
                $hObject['lastModified'] = (string)$oContent->LastModified;
                $hObject['ETag'] = htmlspecialchars_decode((string)$oContent->ETag);
                $hObject['size'] = (int)$oContent->Size;
                $hObject['owner']['id'] = (string)$oContent->Owner->ID;
                $hObject['owner']['name'] = (string)$oContent->Owner->DisplayName;
                $hObject['storage'] = (string)$oContent->StorageClass;

                $aContent[] = $hObject;
            }
        } while ($bTruncated && sizeof($aContent) < $maxKeys);

        return $aContent;
    }

    public function bucketGetRequestPayment($sBucket)
    {
        // FIXME same issue, we need host: + /?requestPayment
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] =  'GET';
        $hRequest['resource'] = $sBucket . '?requestPayment';
        $hResult = $this->sendRequest($hRequest);

        $oXML = simplexml_load_string($hResult['body']);
        if ($oXML->Code) {
            throw new Exception((string)$oXML->Message);
        }

        return (string)$oXML->Payer;
    }

    public function bucketLocation($sBucket)
    {
        // FIXME this is not working because it has to be /?location + Host: bucket .s3.a....
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] =  'GET';
        $hRequest['resource'] = $sBucket . '?location';
        $hResult = $this->sendRequest($hRequest);

        $oXML = simplexml_load_string($hResult['body']);
        if ($oXML->Code) {
            throw new Exception((string)$oXML->Message);
        }

        return (string)$oXML->LocationConstraint;
    }

    public function objectPut($sBucket, $sObject, $sValue, $bPublic = null, $hHeaders = array(), $hMetaData = array())
    {
        $acl = isset($bPublic) && $bPublic ? 'public-read' : null;
        $sObject = $sObject[0] == '/' ? $sObject : '/' . $sObject;
        $sMime = null;
        $hRequest = array();

        foreach ($hHeaders as $sKey => $sValue) {
            if (strtolower($sKey) == 'cache-control') {
                $hRequest['headers']['Cache-Control'] = $sValue;
            } else if (strtolower($sKey) == 'content-type') {
                $sMime = $sValue;
            } else if (strtolower($sKey) == 'content-disposition') {
                $hRequest['headers']['Content-Disposition'] = $sValue;
            } else if (strtolower($sKey) == 'content-encoding') {
                $hRequest['headers']['Content-Encoding'] = $sValue;
            }
        }

        foreach ($hMetaData as $sKey => $sValue) {
            $hRequest['headers']['x-amz-meta-'.$sKey] = $sValue;
        }

        $hRequest['verb'] =  'PUT';
        $hRequest['resource'] = '/'.$sBucket.$sObject;
        $hRequest['type'] = $sMime !== null ? $sMime : 'text/plain';
        $hRequest['acl'] = $acl;

        $sBody = $sValue;

        $hResult = $this->sendRequest($hRequest,$sBody);
        return $this->positive($hResult);
    }

    public function objectUpload($sBucket, $sObject, $sFilename, $bPublic = null, $hHeaders = array(), $hMetaData = array())
    {
        $acl = isset($bPublic) && $bPublic ? 'public-read' : null;
        $sObject = $sObject[0] == '/' ? $sObject : '/' . $sObject;
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;
        $sMime = null;
        $hRequest = array();

        foreach ($hHeaders as $sKey => $sValue) {
            if (strtolower($sKey) == 'cache-control') {
                $hRequest['headers']['Cache-Control'] = $sValue;
            } else if (strtolower($sKey) == 'content-type') {
                $sMime = $sValue;
            } else if (strtolower($sKey) == 'content-disposition') {
                $hRequest['headers']['Content-Disposition'] = $sValue;
            } else if (strtolower($sKey) == 'content-encoding') {
                $hRequest['headers']['Content-Encoding'] = $sValue;
            }
        }

        $sType = self::mime_type($sFilename);

        foreach ($hMetaData as $sKey => $sValue) {
            $hRequest['headers']['x-amz-meta-'.$sKey] = $sValue;
        }

        $hRequest['verb'] =  'PUT';
        $hRequest['resource'] = $sBucket.$sObject;
        $hRequest['upload'] = $sFilename;
        $hRequest['type'] = $sMime !== null ? $sMime : $sType;
        $hRequest['acl'] = $acl;

        $hResult = $this->sendRequest($hRequest);
        return $this->positive($hResult);
    }

    public function objectCopy($sSourceBucket, $sSourceObject, $sDestBucket, $sDestObject)
    {
        $sSourceObject = $sSourceObject[0] == '/' ? $sSourceObject : '/' . $sSourceObject;
        $sSourceBucket = $sSourceBucket[0] == '/' ? $sSourceBucket : '/' . $sSourceBucket;

        $sDestObject = $sDestObject[0] == '/' ? $sDestObject : '/' . $sDestObject;
        $sDestObject = $sDestObject[0] == '/' ? $sDestObject : '/' . $sDestObject;

        $hRequest['verb'] =  'PUT';
        $hRequest['resource'] = $sDestBucket.$sDestObject;
        $hRequest['headers']['x-amz-copy-source'] = $sSourceBucket.$sSourceObject;

        $hResult = $this->sendRequest($hRequest);
        return $this->positive($hResult);
 
    }

    public function objectGet($sBucket, $sObject) 
    {
        $sObject = $sObject[0] == '/' ? $sObject : '/' . $sObject;
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] = 'GET';
        $hRequest['resource'] = $sBucket.$sObject;

        $hResult = $this->sendRequest($hRequest);

        if ($this->positive($hResult)) {
            return $hResult['body'];
        }
    }

    public function objectDownload($sBucket, $sObject, $sFilename)
    {
        $sObject = $sObject[0] == '/' ? $sObject : '/' . $sObject;
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] =  'GET';
        $hRequest['resource'] = $sBucket.$sObject;
        $hRequest['download'] = $sFilename;

        $hResult = $this->sendRequest($hRequest);

        return $this->positive($hResult);
    }

    public function objectHead($sBucket, $sObject)
    {
        $sObject = $sObject[0] == '/' ? $sObject : '/' . $sObject;
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] =  'HEAD';
        $hRequest['resource'] = $sBucket.$sObject;

        $hResult = $this->sendRequest($hRequest);

        return $hResult['headers'];
    }


    public function objectDelete($bucket, $object) 
    {
        $sObject = $sObject[0] == '/' ? $sObject : '/' . $sObject;
        $sBucket = $sBucket[0] == '/' ? $sBucket : '/' . $sBucket;

        $hRequest = array();
        $hRequest['verb'] =  'DELETE';
        $hRequest['resource'] = $sBucket.$sObject;

        $hResult = $this->sendRequest($hRequest);

        return $this->positive($hResult);
    }


    private function sendRequest($req, $params = null) 
    {
        if (isset($req['resource'])) {
            $req['resource'] = urlencode($req['resource']);
            $req['resource'] = str_replace('%2F', '/', $req['resource']);
        }

        $sURL = 'http://'.$this->sServer.$req['resource'];
        $sig = $this->signature($req);

        if (sizeof($params) && strtoupper($req['verb']) == 'GET') {
            $sURL .= '?';
            foreach( $params as $key => $val ) {
                $sURL .= $key . '=' . urlencode($val) . '&';
            }
        }

        $aHeaders = array();

        $aHeaders[] = 'Pragma:';
        $aHeaders[] = 'Accept:';
        $aHeaders[] = 'Content-Type:';
        $aHeaders[] = 'Expect:';
        $aHeaders[] = 'Connection: close';
        $aHeaders[] = 'Date: '.$this->sDate;
        $aHeaders[] = 'Authorization: AWS '.$this->sKey.':'.$sig;

        if (!is_array($params) && strlen($params) > 1) {
            $aHeaders[] = 'Content-Length:'.strlen($params);
        } else {
            $aHeaders[] = 'Content-Length:';
        }


        if (isset($req['acl'])) {
            $aHeaders[] = 'x-amz-acl: '.$req['acl'];
        }
        if (isset($req['type'])) {
            $aHeaders[] = 'Content-Type: '.$req['type'];
        }
        if (isset($req['md5'])) {
            $aHeaders = 'Content-Md5: '.$req['md5'];
        }
        if (isset($req['disposition'])) {
            $aHeaders[] = 'Content-Disposition: attachment; filename=\"' . $req['disposition'] . '\"';
        }

        if (is_array($req['headers'])) {
            foreach($req['headers'] as $key => $val) {
                $aHeaders[] = $key.': '.$val;
            }
        }

        $oCurl = curl_init();
        // This is the maximum execution time fo the curl_exec function ...
        //TODO curl_setopt($oCurl, CURLOPT_TIMEOUT, 30);
        curl_setopt($oCurl,CURLOPT_CONNECTTIMEOUT,4); 
        curl_setopt($oCurl, CURLOPT_VERBOSE, 1);

        if ($req['upload']) {
            $fd = fopen($req['upload'],'rb');
            if ($fd) {
                curl_setopt($oCurl, CURLOPT_INFILE, $fd);
                curl_setopt($oCurl, CURLOPT_UPLOAD, 1);
                curl_setopt($oCurl, CURLOPT_INFILESIZE, filesize($req['upload']));
            } else {
                throw new Exception('Could not open file: '.$req['upload']);
            }
        } 

        if ($req['download']) {
            $fd = fopen($req['download'],'wb');
            if ($fd) {
                curl_setopt($oCurl, CURLOPT_FILE, $fd);
                //curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 0);
                curl_setopt($oCurl, CURLOPT_HEADER, 0);

                $sTmp = tempnam('/home/20807/data/tmp', 's3_header_');
                $hd = fopen($sTmp,'w');
                if ($hd) {
                    curl_setopt($oCurl, CURLOPT_WRITEHEADER, $hd);
                }
            } else {
                throw new Exception('Could not open file: '.$req['download']);
            }
        } else {
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($oCurl, CURLOPT_HEADER, 1);
        }

        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $aHeaders);
        curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, $req['verb']);
        if (strtoupper($req['verb']) != 'GET') {
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($oCurl, CURLOPT_USERAGENT, 'PHP '.phpversion().' S3 frencaze library 0.1');


        $sResult = curl_exec($oCurl);

        if (($req['upload'] || $req['download']) && $fd) {
            fclose($fd);
        }
        if ($req['download'] && $hd) {
            fclose($hd);
            $sResult = file_get_contents($sTmp);
            unlink($sTmp);
        }

        $aHeaders    = array();
        $iHeaderSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        $sHeaders    = substr($sResult, 0, $iHeaderSize - 4);
        $aLines    = explode("\r\n",$sHeaders);
        foreach ($aLines as $sLine) {
            if (strpos($sLine, ':')) {
                $sKey = substr($sLine, 0, strpos($sLine, ':'));
                $sValue = trim(substr($sLine, strpos($sLine, ':') + 1));
                $aHeaders[$sKey] = $sValue; 
            } else {
                $aHeaders[] = $sLine;
            }
        }
        $sResult     = substr($sResult, $iHeaderSize);

        $hResult = array();
        $hResult['headers'] = $aHeaders;
        $hResult['body']    = $sResult;
        $hResult['code']    = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);

        $hStats = array();
        $hStats['filetime']                = curl_getinfo($oCurl, CURLINFO_FILETIME);
        $hStats['total_time']              = curl_getinfo($oCurl, CURLINFO_TOTAL_TIME);
        $hStats['namelookup_time']         = curl_getinfo($oCurl, CURLINFO_NAMELOOKUP_TIME);
        $hStats['connect_time']            = curl_getinfo($oCurl, CURLINFO_CONNECT_TIME);
        $hStats['pretransfer_time']        = curl_getinfo($oCurl, CURLINFO_PRETRANSFER_TIME);
        $hStats['starttransfer_time']      = curl_getinfo($oCurl, CURLINFO_STARTTRANSFER_TIME);
        $hStats['redirect_time']           = curl_getinfo($oCurl, CURLINFO_REDIRECT_TIME);
        $hStats['size_upload']             = curl_getinfo($oCurl, CURLINFO_SIZE_UPLOAD);
        $hStats['size_download']           = curl_getinfo($oCurl, CURLINFO_SIZE_DOWNLOAD);
        $hStats['speed_download']          = curl_getinfo($oCurl, CURLINFO_SPEED_DOWNLOAD);
        $hStats['speed_upload']            = curl_getinfo($oCurl, CURLINFO_SPEED_UPLOAD);
        $hStats['content_length_download'] = curl_getinfo($oCurl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $hStats['content_length_upload']   = curl_getinfo($oCurl, CURLINFO_CONTENT_LENGTH_UPLOAD);
        $hStats['content_type']            = curl_getinfo($oCurl, CURLINFO_CONTENT_TYPE);

        $hResult['stats'] = $hStats;
        curl_close($oCurl);

        return $hResult;
    }

    private function positive($hResult) 
    {
        $iCode = $hResult['code'];
        return ( $iCode >= 200 && $iCode < 300 );
    }

    private function signature($req) 
    {
        // Format and sort x-amz headers
        $arrHeaders = array();
        if (!is_array($req['headers'])) {
            $req['headers'] = array();
        }
        if (isset($req['acl'])) {
            $req['headers']['x-amz-acl'] = $req['acl'];
        }
        foreach ($req['headers'] as $key => $val)
        {
            $key = trim(strtolower($key));
            $val = trim($val);
            if (strpos($key, "x-amz") !== false)
            {
                if (isset($arrHeaders[$key])) {
                    $arrHeaders[$key] .= ','.$val;
                } else {
                    $arrHeaders[$key] = $key.':'.$val;
                }
            }
        }

        ksort($arrHeaders);
        $headers = implode("\n", $arrHeaders);
        if (!empty($headers)) {
            $headers = "\n$headers";
        }

        if (isset($req['date'])) {
            $this->sDate = gmdate('D, d M Y H:i:s T', strtotime($req['date']));
        } else {
            $this->sDate = gmdate('D, d M Y H:i:s T');
        }

        // Build and sign the string
        $str  = strtoupper($req['verb']) . "\n" . $req['md5']  . "\n" . $req['type'] . "\n" . $this->sDate . $headers . "\n" . $req['resource'];
        $sha1 = $this->hasher($str);
        $sig  = $this->base64($sha1);
        return $sig;
    }

    private function hasher($data) 
    {
        // Algorithm adapted (stolen)
        // from http://pear.php.net/package/Crypt_HMAC/)
        $key = $this->sSecret;
        if (strlen($key) > 64) {
            $key = pack('H40', sha1($key));
        }

        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        $ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));
        return sha1($opad . pack('H40', sha1($ipad . $data)));
    }

    private function base64($str) 
    {
        $ret = '';
        for ($i = 0; $i < strlen($str); $i += 2) {
            $ret .= chr(hexdec(substr($str, $i, 2)));
        }
        return base64_encode($ret);
    }

    public static function mime_type($sFile)
    {
        $sMime = mime_content_type($sFile);

        $hMap = array(
                'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png',
                'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon',
                'swf' => 'application/x-shockwave-flash', 'pdf' => 'application/pdf',
                'zip' => 'application/zip', 'gz' => 'application/x-gzip',
                'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
                'bz2' => 'application/x-bzip2', 'txt' => 'text/plain',
                'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
                'css' => 'text/css', 'js' => 'text/javascript',
                'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
                'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
                'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
                'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
                );
        $sExt = strtolower(pathinfo($sFile, PATHINFO_EXTENSION));
        return isset($hMap[$sExt]) ? $hMap[$sExt] : ($sMime ? $sMime : 'application/octet-stream');
    }

}

