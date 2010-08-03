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

class Smugmug 
{
    protected $sKey;
    protected $sURL;

    protected $sSession;

    public static $aURLs = array( 'OriginalURL', 'LargeURL', 'MediumURL', 'SmallURL', 'ThumbURL', 'TinyURL', 'AlbumURL' );

    public function __construct($sKey) 
    {
        $this->sKey = $sKey;

        $this->sURL = 'api.smugmug.com/hack/php/1.2.0/';
    }

    public function setSession($sSession) 
    {
        $this->sSession = $sSession;
    }

    public function login_withPassword($sEmail,$sPassword) 
    {
        $hParams = array();
        $hParams['EmailAddress'] = $sEmail;
        $hParams['Password']     = $sPassword;
        $hResult = $this->callMethod('smugmug.login.withPassword',$hParams,true);

        if ($hResult['Login'] && $hResult['Login']['Session']) {
            $this->sSession = $hResult['Login']['Session']['id'];
        }
        return $hResult;
    }

    public function login_withHash($iUserId,$sHash) 
    {
        $hParams = array();
        $hParams['UserID']       = $iUserId;
        $hParams['PasswordHash'] = $sHash;
        $hResult = $this->callMethod('smugmug.login.withHash',$hParams,true);
        if ($hResult['Login'] && $hResult['Login']['Session']) {
            $this->sSession = $hResult['Login']['Session']['id'];
        }
        return $hResult;
    }

    public function login_anonymously() 
    {
        $hParams = array();
        $hResult = $this->callMethod('smugmug.login.anonymously',$hParams);
        if ($hResult['Login'] && $hResult['Login']['Session']) {
            $this->sSession = $hResult['Login']['Session']['id'];
        }
        return $hResult;
    }

    public function logout() 
    {
        if (empty($this->sSession)) {
            return;
        }
        $hParams = array();
        $hParams['SessionID'] = $this->sSession;
        $hResult = $this->callMethod('smugmug.logout',$hParams);
        return $hResult;
    }

    public function users_getTree($sNickname=null,$bHeavy=false,$sSitePassword=false) 
    {
        if (empty($this->sSession)) {
            return;
        }
        $hParams = array();
        $hParams['SessionID'] = $this->sSession;
        if ($sNickname !== null) {
            $hParams['Nickname']     = $sNickname;
        }
        if ($bHeavy !== null) {
            $hParams['Heavy']     = $bHeavy;
        }
        if ($sSitePassword !== null) {
            $hParams['SitePassword']     = $sSitePassword;
        }
        $hResult = $this->callMethod('smugmug.users.getTree',$hParams);
        return $hResult;
    }

    public function users_getTransferStats($iMonth,$iYear) 
    {
        if (empty($this->sSession)) {
            return;
        }
        $hParams = array();
        $hParams['SessionID'] = $this->sSession;
        $hParams['Month']     = $iMonth;
        $hParams['Year']      = $iYear;
        $hResult = $this->callMethod('smugmug.users.getTransferStats',$hParams);
        return $hResult;
    }

    public function albums_get($sNickname=null,$bHeavy=null,$sSitePassword=null) 
    {
        if (empty($this->sSession)) {
            throw new Exception('Not logged in');
        }
        $hParams = array();
        $hParams['SessionID'] = $this->sSession;
        if ($sNickname !== null) {
            $hParams['NickName'] = $sNickname;
        }
        if ($bHeavy !== null) {
            $hParams['Heavy'] = $bHeavy;
        }
        if ($sSitePassword !== null) {
            $hParams['SitePassword'] = $sSitePassword;
        }
        $hResult = $this->callMethod('smugmug.albums.get',$hParams);

        return $hResult['Albums'];
    }

    public function albums_getInfo($mAlbumId,$sPassword=null,$sSitePassword=null)
    {
        if (empty($this->sSession)) {
            return;
        }

        if (is_numeric($mAlbumId)) {
            $aAlbums = array(array('id' => $mAlbumId));
        } else {
            $aAlbums = $mAlbumId;
        }

        $aAlbumsInfo = array();
        foreach( $aAlbums as $hAlbum ) {
            $iAlbumId = $hAlbum['id'];
            $hParams = array();
            $hParams['SessionID'] = $this->sSession;
            $hParams['AlbumID']   = $iAlbumId;
            if ($sPassword !== null) {
                $hParams['Password'] = $sPassword;
            }
            if ($sSitePassword !== null) {
                $hParams['SitePassword'] = $sSitePassword;
            }
            $hResult = $this->callMethod('smugmug.albums.getInfo',$hParams);
            $aAlbumsInfo[$iAlbumId] = $hResult['Album'];
        }

        if (is_numeric($mAlbumId)) {
            return $aAlbumsInfo[$mAlbumId];
        } else {
            return $aAlbumsInfo;
        }
    }

    public function images_get($iAlbumId,$bHeavy=null,$sPassword=null,$sSitePassword=null) 
    {
        if (empty($this->sSession)) {
            return;
        }
        $hParams = array();
        $hParams['SessionID'] = $this->sSession;
        $hParams['AlbumID'] = $iAlbumId;
        if ($bHeavy !== null) {
            $hParams['Heavy'] = $bHeavy;
        }
        if ($sPassword !== null) {
            $hParams['Password'] = $sPassword;
        }
        if ($sSitePassword !== null) {
            $hParams['SitePassword'] = $sSitePassword;
        }
        $hResult = $this->callMethod('smugmug.images.get',$hParams);

        return $hResult['Images'];
    }

    public function images_getInfo($iImageId,$sPassword=null,$sSitePassword=null) 
    {
        if (empty($this->sSession)) {
            return;
        }
        $hParams = array();
        $hParams['SessionID'] = $this->sSession;
        $hParams['ImageID'] = $iImageId;
        if ($sPassword !== null) {
            $hParams['Password'] = $sPassword;
        }
        if ($sSitePassword !== null) {
            $hParams['SitePassword'] = $sSitePassword;
        }
        $hResult = $this->callMethod('smugmug.images.getInfo',$hParams);
        return $hResult['Image'];
    }

    public function images_getURLs($iImageId,$sPassword=null,$sSitePassword=null) 
    {
        if (empty($this->sSession)) {
            return;
        }
        $hParams = array();
        $hParams['SessionID'] = $this->sSession;
        $hParams['ImageID'] = $iImageId;
        if ($sPassword !== null) {
            $hParams['Password'] = $sPassword;
        }
        if ($sSitePassword !== null) {
            $hParams['SitePassword'] = $sSitePassword;
        }
        $hResult = $this->callMethod('smugmug.images.getURLs',$hParams);
        return $hResult['Image'];
    }


    public function images_getExif($iImageId,$sPassword=null,$sSitePassword=null) 
    {
        if (empty($this->sSession)) {
            return;
        }
        $hParams = array();
        $hParams['SessionID'] = $this->sSession;
        $hParams['ImageID'] = $iImageId;
        if ($sPassword !== null) {
            $hParams['Password'] = $sPassword;
        }
        if ($sSitePassword !== null) {
            $hParams['SitePassword'] = $sSitePassword;
        }
        $hResult = $this->callMethod('smugmug.images.getEXIF',$hParams);
        return $hResult['Image'];
    }

    public function callMethod($sMethod,$hParams,$bSecure = false) 
    {
        $hPostParams = array();
        $hPostParams['method'] = $sMethod;
        $hPostParams['APIKey'] = $this->sKey;
        foreach( $hParams as $key => $value ) {
            $hPostParams[$key] = $value;
        }


        $sQuery = '';
        foreach( $hPostParams as $key => $value ) {
            $sQuery .= $key.'='.urlencode($value).'&';
        }

        $sURL = $bSecure ? 'https' : 'http';
        $sURL .= '://'.$this->sURL.'?'.$sQuery;

        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 10);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        $sResult = curl_exec($oCurl);
        $iCode = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        curl_close($oCurl);

        $hResult = unserialize($sResult);

        if ($hResult['stat'] == 'fail') {
            throw new Exception($hResult['message'],$hResult['code']);
        }

        return $hResult;
    }
}

