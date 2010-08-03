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

class YouTube
{
    public static function get_video($id)
    {
        $sURL = 'http://gdata.youtube.com/feeds/api/videos/'.$id;
        return self::entry_to_video(file_get_contents($sURL));
    }

    public static function favorites($sUser)
    {
        $aV = YouTube::raw_favorites($sUser);
        $aVideos = array();
        foreach ($aV->feed->entry as $h) {
            $s = $h->link[0]->href;
            $id = substr($s, strpos($s, 'v=') + 2);
            $hVideo = YouTube::get_video($id);
            $hVideo['favorited'] = $h->updated->t;
            $hVideo['link'] = $h->link[0]->href;
            $aVideos[] = $hVideo;
        }
        return $aVideos;
    }
 
    protected static function raw_favorites($sUser)
    {
        $sURL = 'http://gdata.youtube.com/feeds/base/users/'.$sUser.'/favorites?alt=json&v=2&orderby=published&client=ytapi-youtube-profile';
        return json_decode(str_replace('$t', 't', file_get_contents($sURL)));
    }

    public static function entry_to_video($sEntry)
    {
        $sEntry = str_replace('<entry>',"<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gml='http://www.opengis.net/gml' xmlns:georss='http://www.georss.org/georss' xmlns:media='http://search.yahoo.com/mrss/' xmlns:yt='http://gdata.youtube.com/schemas/2007' xmlns:gd='http://schemas.google.com/g/2005'>",$sEntry);

        $oEntry = simplexml_load_string($sEntry);

        $aPlayers = $oEntry->xpath('//media:player');
        $aContent = $oEntry->xpath('//media:content[@type=\'application/x-shockwave-flash\']');
        $aThumbs  = $oEntry->xpath('//media:thumbnail');

        $hVideo = array();
        $sID = (string)$oEntry->id;
        $sID = substr($sID,strrpos($sID,'/')+1);
        $hVideo['id']        = $sID;

        foreach( $aThumbs as $oThumb ) {
            $sURL = (string)$oThumb['url'];
            $i = substr($sURL,strrpos($sURL,'/')+1,1);
            $hVideo['thumbs'][$i] = (string)$oThumb['url'];
        }
        $hVideo['published'] = strtotime((string)$oEntry->published);
        $hVideo['updated']   = strtotime((string)$oEntry->updated);
        $hVideo['published'] = $hVideo['published'] ? $hVideo['published'] : $hVideo['updated'];
        $hVideo['title']     = (string)$oEntry->title;
        $hVideo['content']   = (string)$oEntry->content;
        $hVideo['author']    = (string)$oEntry->author->name;
        return $hVideo;
   }

}

