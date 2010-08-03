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


class iTunes
{
    public static function search_movie_title($sTitle)
    {
        $sURL  = 'http://ax.phobos.apple.com.edgesuite.net/WebObjects';
        $sURL .= '/MZStoreServices.woa/wa/itmsSearch?';
        $sURL .= 'WOURLEncoding=ISO8859_1&lang=1&output=lm';
        $sURL .= '&country=US&term='.urlencode($sTitle).'&media=movie';

        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_URL, $sURL);
        $sResult = curl_exec($oCurl);
        $iCode = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        curl_close($oCurl);

        $aTitles = array();
        $sToken = '<TABLE CELLSPACING=0 CELLPADDING=0 BORDER=0 WIDTH="100%">';
        if (strpos($sResult, $sToken)) {
            $sBody = substr($sResult, strpos($sResult, $sToken) + strlen($sToken));
            $sBody = substr($sBody, 0, strpos($sBody, '</TABLE>'));
            $aParts = explode('<a class="searchResults" href="', $sBody);
            array_shift($aParts);
            foreach ($aParts as $sPart) {
                $sLink = substr($sPart, 0, strpos($sPart, '"'));
                $sLink = urldecode(substr($sLink, strpos($sLink, ';url=') + 5));
                $sToken = '<span class="searchResults">';
                $sTitle = substr($sPart, strpos($sPart, $sToken) + strlen($sToken));
                $sTitle = substr($sTitle, 0, strpos($sTitle, '</span>'));
                $aTitles[] = array('title' => $sTitle, 'link' => $sLink);
            }
        }

        return $aTitles;
    }
}

