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


class Vimeo
{
    public function info($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/info.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function clips($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/clips.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function appears_in($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/appears_in.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function all_clips($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/all_clips.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function subscriptions($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/subscriptions.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function albums($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/albums.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function channels($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/channels.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function groups($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/groups.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function contacts_clips($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/contacts_clips.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function contacts_like($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/contacts_like.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function video($sVideo)
    {
        $sURL = 'http://vimeo.com/api/clip/'.$sUser.'.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function likes($sUser)
    {
        $sURL = 'http://vimeo.com/api/'.$sUser.'/likes.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function user_did($sUser)
    {
        $sURL = 'http://vimeo.com/api/activity/'.$sUser.'/user_did.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function happened_to_user($sUser)
    {
        $sURL = 'http://vimeo.com/api/activity/'.$sUser.'/happened_to_user.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function contacts_did($sUser)
    {
        $sURL = 'http://vimeo.com/api/activity/'.$sUser.'/contacts_did.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function happened_to_contacts($sUser)
    {
        $sURL = 'http://vimeo.com/api/activity/'.$sUser.'/happened_to_contacts.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function everyone_did($sUser)
    {
        $sURL = 'http://vimeo.com/api/activity/'.$sUser.'/everyone_did.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function group_clips($sGroup)
    {
        $sURL = 'http://vimeo.com/api/group/'.$sGroup.'/clips.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function group_users($sGroup)
    {
        $sURL = 'http://vimeo.com/api/group/'.$sGroup.'/users.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function group_info($sGroup)
    {
        $sURL = 'http://vimeo.com/api/group/'.$sGroup.'/info.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function channel_clips($sChannel)
    {
        $sURL = 'http://vimeo.com/api/channel/'.$sChannel.'/clips.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function channel_info($sChannel)
    {
        $sURL = 'http://vimeo.com/api/channel/'.$sChannel.'/info.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function album_clips($sAlbum)
    {
        $sURL = 'http://vimeo.com/api/album/'.$sAlbum.'/clips.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    public function album_info($sAlbum)
    {
        $sURL = 'http://vimeo.com/api/album/'.$sAlbum.'/info.php';
        $hResult = $this->doRequest($sURL);
        return unserialize($hResult['body']);
    }

    protected function doRequest($sURL, $hParams = array())
    {
        $aParams = array();
        if ($hParams && is_array($hParams) && sizeof($hParams)) {
            foreach ($hParams as $sKey => $sValue) {
                $aParams[] = $sKey.'='.urlencode($sValue);
            }
            $sParams = implode('&', $aParams);
            $sURL .= '?' . implode('&', $hParams);
        }
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);
        if ($this->iTimeout > 0) {
            curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->iTimeout);
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
            }
        }
        $sResult     = substr($sResult, $iHeaderSize);

        return array('code' => $iCode, 'headers' => $aHeaders, 'body' => $sResult);
    }
}

