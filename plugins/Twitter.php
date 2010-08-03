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

class Twitter
{
    protected $sLogin;
    protected $sPassword;
    protected $iTimeout;
    protected $iRateLimit;
    protected $iRateRemaining;
    protected $iRateReset;

    public function __construct($sLogin = null, $sPassword = null)
    {
        $this->sLogin = $sLogin;
        $this->sPassword = $sPassword;
    }

    public function setTimeout($iTimeout)
    {
        $this->iTimeout = $iTimeout;
    }

    protected function doRequest($sURL, $hParams = array(), $bAuthenticate = false, $bPost = false)
    {
        $aParams = array();
        if ($hParams && is_array($hParams) && sizeof($hParams)) {
            foreach ($hParams as $sKey => $sValue) {
                $aParams[] = $sKey.'='.urlencode($sValue);
            }
        }

        if (sizeof($aParams)) {
            $sParams = implode('&', $aParams);
        }
        if (!$bPost && strlen($sParams)) {
            $sURL .= '?' . $sParams;
        }
        _DEBUG('url = '.$sURL);

        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);
        if ($this->iTimeout > 0) {
            curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->iTimeout);
        }
        if ($bPost) {
            curl_setopt($oCurl, CURLOPT_POST, 1);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $sParams);
        }
        if ($bAuthenticate) {
            if (!$this->sLogin || !$this->sPassword) {
                throw new Exception('Authentication required');
            }
        }

        // Authenticate if required or if we have login/password
        if ($bAuthenticate || ($this->sLogin && $this->sPassword)) {
            curl_setopt($oCurl, CURLOPT_USERPWD, $this->sLogin.':'.$this->sPassword);
        }

        $sResult = curl_exec($oCurl);
        if (!$sResult) {
            return null;
        }
        $iCode = curl_getinfo($oCurl,  CURLINFO_HTTP_CODE);
        $iHeaderSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        curl_close($oCurl);

        $aHeaders    = array();
        $sHeaders    = substr($sResult, 0, $iHeaderSize - 4);
        $aLines    = explode("\r\n",$sHeaders);
        foreach ($aLines as $sLine) {
            if (strpos($sLine, ':') !== false) {
                $sKey = substr($sLine, 0, strpos($sLine, ':'));
                $sValue = trim(substr($sLine, strpos($sLine, ':') + 1));
                $aHeaders[$sKey] = $sValue;
                if (strtolower($sKey) == 'x-ratelimit-limit') {
                    $this->iRateLimit = $sValue;
                } else if (strtolower($sKey) == 'x-ratelimit-remaining') {
                    $this->iRateRemaining = $sValue;
                } else if (strtolower($sKey) == 'x-ratelimit-reset') {
                    $this->iRateReset = $sValue;
                }
            }
        }
        $sResult     = substr($sResult, $iHeaderSize);

        return array('code' => $iCode, 'body' => $sResult, 'headers' => $aHeaders);
    }

    public function publicTimeline($iPage = null, $iCount = null)
    {
        $sURL = 'http://twitter.com/statuses/public_timeline.json';
        $hParams = array();
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        if ($iCount !== null) {
            $hParams['count'] = $iCount;
        }
        $hRes = $this->doRequest($sURL, $hParams);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function friendsTimeline($iSinceId = null, $iMaxId = null,$iPage = null, $iCount = null)
    {
        $sURL = 'http://twitter.com/statuses/friends_timeline.json';
        $hParams = array();
        if ($iSinceId !== null) {
            $hParams['since_id'] = $iSinceId;
        }
        if ($iMaxId !== null) {
            $hParams['max_id'] = $iMaxId;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        if ($iCount !== null) {
            $hParams['count'] = $iCount;
        }
        $hRes = $this->doRequest($sURL, $hParams, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function userTimeline($iId = null, $iUserId = null, $sScreenName = null, $iSinceId = null, $iMaxId = null,$iPage = null, $iCount = null)
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/statuses/user_timeline'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/statuses/user_timeline.json';
        }
        $hParams = array();
        if ($iUserId !== null) {
            $hParams['user_id'] = $iUserId;
        }
        if ($sScreenName !== null) {
            $hParams['screen_name'] = $sScreenName;
        }
        if ($iSinceId !== null) {
            $hParams['since_id'] = $iSinceId;
        }
        if ($iMaxId !== null) {
            $hParams['max_id'] = $iMaxId;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        if ($iCount !== null) {
            $hParams['count'] = $iCount;
        }
        $hRes = $this->doRequest($sURL, $hParams, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function mentions($iSinceId = null, $iMaxId = null,$iPage = null, $iCount = null)
    {
        $sURL = 'http://twitter.com/statuses/mentions.json';
        $hParams = array();
        if ($iSinceId !== null) {
            $hParams['since_id'] = $iSinceId;
        }
        if ($iMaxId !== null) {
            $hParams['max_id'] = $iMaxId;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        if ($iCount !== null) {
            $hParams['count'] = $iCount;
        }
        $hRes = $this->doRequest($sURL, $hParams, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function statusShow($iId)
    {
        $sURL = 'http://twitter.com/statuses/show'.$iId.'.json';
        $hRes = $this->doRequest($sURL, null, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function statusUpdate($sStatus, $iInReplyToStatusId = null)
    {
        $sURL = 'http://twitter.com/statuses/update.json';
        $hParams = array(); 
        $hParams['status'] = $sStatus;
        if ($iInReplyToStatusId !== null) {
            $hParams['in_reply_to_status_id'] = $iInReplyToStatusId;
        }
        $hRes = $this->doRequest($sURL, $hParams, true, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function statusDestroy($iStatus)
    {
        $sURL = 'http://twitter.com/statuses/destroy/'.$iStatus.'.json';
        $hRes = $this->doRequest($sURL, null, true, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function userShow($iId = null, $iUserId = null, $sScreenName = null) 
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/users/show/'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/users/show.json';
        }
        $hParams = array();
        if ($iUserId !== null) {
            $hParams['user_id'] = $iUserId;
        }
        if ($sScreenName !== null) {
            $hParams['screen_name'] = $sScreenName;
        }
        $hRes = $this->doRequest($sURL, $hParams);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function friends($iId = null, $iUserId = null, $sScreenName = null, $iPage = null) 
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/statuses/friends'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/statuses/friends.json';
        }
        $hParams = array();
        if ($iUserId !== null) {
            $hParams['user_id'] = $iUserId;
        }
        if ($sScreenName !== null) {
            $hParams['screen_name'] = $sScreenName;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        $hRes = $this->doRequest($sURL, $hParams);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function followers($iId = null, $iUserId = null, $sScreenName = null, $iPage = null) 
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/statuses/followers'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/statuses/followers.json';
        }
        $hParams = array();
        if ($iUserId !== null) {
            $hParams['user_id'] = $iUserId;
        }
        if ($sScreenName !== null) {
            $hParams['screen_name'] = $sScreenName;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        $hRes = $this->doRequest($sURL, $hParams);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function directMessages($iSinceId = null, $iMaxId = null,$iPage = null, $iCount = null)
    {
        $sURL = 'http://twitter.com/direct_messages.json';
        $hParams = array();
        if ($iSinceId !== null) {
            $hParams['since_id'] = $iSinceId;
        }
        if ($iMaxId !== null) {
            $hParams['max_id'] = $iMaxId;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        if ($iCount !== null) {
            $hParams['count'] = $iCount;
        }
        $hRes = $this->doRequest($sURL, $hParams, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function directMessagesSent($iSinceId = null, $iMaxId = null,$iPage = null)
    {
        $sURL = 'http://twitter.com/direct_messages/sent.json';
        $hParams = array();
        if ($iSinceId !== null) {
            $hParams['since_id'] = $iSinceId;
        }
        if ($iMaxId !== null) {
            $hParams['max_id'] = $iMaxId;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        $hRes = $this->doRequest($sURL, $hParams, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function directMessagesNew($iUser, $sStatus)
    {
        $sURL = 'http://twitter.com/direct_messages/new.json';
        $hParams = array();
        $hParams['user'] = $iUser;
        $hParams['status'] = $sStatus;
        $hRes = $this->doRequest($sURL, $hParams, true, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function directMessagesDestroy($iDirectMessage)
    {
        $sURL = 'http://twitter.com/direct_messages/destroy/'.$iDirectMessage.'.json';
        $hRes = $this->doRequest($sURL, null, true, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function friendshipCreate($iId = null, $iUserId = null, $sScreenName = null, $bFollow = null)
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/friendships/create/'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/friendships/create.json';
        }
        $hParams = array();
        if ($iUserId !== null) {
            $hParams['user_id'] = $iUserId;
        }
        if ($sScreenName !== null) {
            $hParams['screen_name'] = $sScreenName;
        }
        if ($iSinceId !== null) {
            $hParams['since_id'] = $iSinceId;
        }
        if ($bFollow !== null) {
            $hParams['follow'] = $bFollow ? 'true' : 'false';
        }
        $hRes = $this->doRequest($sURL, $hParams, true, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function friendshipDestroy($iId = null, $iUserId = null, $sScreenName = null)
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/friendships/destroy/'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/friendships/destroy.json';
        }
        $hParams = array();
        if ($iUserId !== null) {
            $hParams['user_id'] = $iUserId;
        }
        if ($sScreenName !== null) {
            $hParams['screen_name'] = $sScreenName;
        }
        if ($iSinceId !== null) {
            $hParams['since_id'] = $iSinceId;
        }
        $hRes = $this->doRequest($sURL, $hParams, true, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function friendshipExists($iUserA, $iUserB)
    {
        $sURL = 'http://twitter.com/friendships/exists.json';
        $hParams = array();
        $hParams['user_a'] = $iUserA;
        $hParams['user_b'] = $iUserB;
        $hRes = $this->doRequest($sURL, $hParams, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function friendsIds($iId = null, $iUserId = null, $sScreenName = null, $iPage = null)
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/friends/ids/'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/friends/ids.json';
        }
        $hParams = array();
        if ($iUserId !== null) {
            $hParams['user_id'] = $iUserId;
        }
        if ($sScreenName !== null) {
            $hParams['screen_name'] = $sScreenName;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        $hRes = $this->doRequest($sURL, $hParams);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function followerIds($iId = null, $iUserId = null, $sScreenName = null, $iPage = null)
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/followers/ids/'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/followers/ids.json';
        }
        $hParams = array();
        if ($iUserId !== null) {
            $hParams['user_id'] = $iUserId;
        }
        if ($sScreenName !== null) {
            $hParams['screen_name'] = $sScreenName;
        }
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        $hRes = $this->doRequest($sURL, $hParams);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function favorites($iId = null, $iPage = null)
    {
        if ($iId !== null) {
            $sURL = 'http://twitter.com/favorites/'.$iId.'.json';
        } else {
            $sURL = 'http://twitter.com/favorites.json';
        }
        $hParams = array();
        if ($iPage !== null) {
            $hParams['page'] = $iPage;
        }
        $hRes = $this->doRequest($sURL, $hParams, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function favoritesCreate($iId)
    {
        $sURL = 'http://twitter.com/favorites/create/'.$iId.'.json';
        $hRes = $this->doRequest($sURL, null, true, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function favoritesDestroy($iId)
    {
        $sURL = 'http://twitter.com/favorites/destroy/'.$iId.'.json';
        $hRes = $this->doRequest($sURL, null, true, true);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }

    public function accountRateLimitStatus($bAuth = true)
    {
        $sURL = 'http://twitter.com/account/rate_limit_status.json';
        $hRes = $this->doRequest($sURL, null, $bAuth);
        if ($hRes) {
            $oResp = json_decode($hRes['body']);
            if ($oResp->error) {
                throw new Exception($oResp->error);
            }
            return $oResp;
        }
    }


    public function getRateLimit()
    {
        if ($this->iRateLimit) {
            return array('limit' => $this->iRateLimit, 'remaining' => $this->iRateRemaining, 'reset' => $this->iRateReset);
        } else {
            return null;
        }
    } 


/* OLD STUFF */
    static public function timeline($sUser, $iMax = null) 
    {
        $sURL = 'http://twitter.com/statuses/user_timeline/'.$sUser.'.json';
        if ($iMax != null) {
            $sURL .= '?count='.$iMax;
        }

        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 2 );

        $sResult = curl_exec($oCurl);
        $iCode = curl_getinfo($oCurl,  CURLINFO_HTTP_CODE);
        curl_close($oCurl);

        $oResp = json_decode($sResult);
        if ($oResp->error) {
            throw new Exception($oResp->error);
        }
        return $oResp;
    }


    static public function update($sLogin, $sPassword, $sStatus)
    {
        $sURL = 'http://twitter.com/statuses/update.json';

        $sParams = 'status='.urlencode($sStatus);

        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, 1);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $sParams);
        curl_setopt($oCurl, CURLOPT_USERPWD, $sLogin.':'.$sPassword);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);

        $sResult = curl_exec($oCurl);
        $iCode = curl_getinfo($oCurl,  CURLINFO_HTTP_CODE);
        $iHeaderSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        curl_close($oCurl);

        $aHeaders    = array();
        $sHeaders    = substr($sResult, 0, $iHeaderSize - 4);
        $aHeaders    = explode("\r\n",$sHeaders);
        $sResult     = substr($sResult, $iHeaderSize);

        $oResp = json_decode($sResult);
        if ($oResp->error) {
            throw new Exception($oResp->error);
        }
        return $oResp;
    }

    static public function show($sLogin, $sPassword)
    {
        $sURL = 'http://twitter.com/users/show.json?screen_name='.urlencode($sLogin);

        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_USERPWD, $sLogin.':'.$sPassword);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);

        $sResult = curl_exec($oCurl);
        $iCode = curl_getinfo($oCurl,  CURLINFO_HTTP_CODE);
        $iHeaderSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        curl_close($oCurl);

        $aHeaders    = array();
        $sHeaders    = substr($sResult, 0, $iHeaderSize - 4);
        $aHeaders    = explode("\r\n",$sHeaders);
        $sResult     = substr($sResult, $iHeaderSize);


        $oResp = json_decode($sResult);
        if ($oResp->error) {
            throw new Exception($oResp->error);
        }
        return $oResp;
    }


}


