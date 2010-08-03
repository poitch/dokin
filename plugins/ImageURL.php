<?php

class ImageURL
{
    public static $sFlickrApiKey;
    public static function get_image_url($sURL)
    {
        if (strstr($sURL,'<img')) {
            // extract src=""
            $sURL = substr($sURL,strpos($sURL,'<img'));
            $sURL = substr($sURL,strpos($sURL,'src="')+5);
            $sURL = substr($sURL,0,strpos($sURL,'"'));
        } else if (strstr($sURL,'[IMG]')) {
            $sURL = str_replace('[IMG]','',$sURL);
            $sURL = str_replace('[/IMG]','',$sURL);
        } else if (strstr($sURL,'photobucket')) {
            if (strstr($sURL,'albums') && !strstr($sURL,'mediadetail')) {
                // sXXX.photobucket.com/albums ( remove anything after ?)
                // append <value> current=<value>
                if (strpos($sURL,'?')) {
                    $sPre = substr($sURL,0,strpos($sURL,'?'));
                    $sPost = substr($sURL,strrpos($sURL,'=')+1);
                    $sURL = $sPre.$sPost;
                }
            } else if (strstr($sURL,'mediadetail')) {
                // photobucket.com/mediadetail/?media=HERE
                $sURL = substr($sURL,strpos($sURL,'?media=')+7);
                $sURL = substr($sURL,0,strpos($sURL,'&'));
                $sURL = urldecode($sURL);
            }
        } else if (strstr($sURL,'flickr')) {
            if (self::$sFlickrApiKey) {
                if (!strstr($sURL,'static')) {
                    // Extract photo id
                    $pid = null;
                    if( strpos($sURL,'photos') ) {
                        // http://www.flickr.com/photos/94234845@N00/1287786206/
                        $sTmp = substr($sURL,strpos($sURL,'photos')+7);
                        $sTmp = substr($sTmp,strpos($sTmp,'/')+1);
    
                        if( strpos($sTmp,'/') ) {
                            $pid = substr($sTmp,0,strpos($sTmp,'/'));
                        } else {
                            $pid = $sTmp;
                        }
                    } else if( strstr($sURL,'photo_zoom') ) {
                        // http://www.flickr.com/photo_zoom.gne?id=1287786206&size=o
                        $sTmp = substr($sURL,strpos($sURL,'id=')+3);
                        if( strpos($sTmp,'&') ) {
                            $pid = substr($sTmp,0,strpos($sTmp,'&'));
                        } else {
                            $pid = $sTmp;
                        }
                    }
    
                    if( $pid ) {
                        $sApiURL = 'http://api.flickr.com/services/rest/?';
                        $sApiURL .= 'method=flickr.photos.getSizes';
                        $sApiURL .= '&api_key='.self::$sFlickrApiKey;
                        $sApiURL .= '&photo_id='.$pid;
                        $sApiURL .= '&format=php_serial';
    
                        $sRsp = file_get_contents($sApiURL);
                        if( $sRsp !== false ) {
                            $oRsp = unserialize($sRsp);
                            $iCurrent = 0;
                            foreach( $oRsp['sizes']['size'] as $hSize ) {
                                if ($iCurrent < 1 && $hSize['label'] == 'Medium') {
                                    $sURL = $hSize['source'];
                                    $iCurrent = 1;
                                } else if ($iCurrent < 2 && $hSize['label'] == 'Large') {
                                    $sURL = $hSize['source'];
                                    $iCurrent = 2;
                                } else if ($iCurrent < 3 && $hSize['label'] == 'Original') {
                                    $sURL = $hSize['source'];
                                    $iCurrent = 3;
                                }
                            }
                        } else {
                            _WARN('Flickr API call failed');
                        }
                    }
                }
            }
        } else if( strstr($sURL,'smugmug') ) {
            // http://nickname.smugmug.com/gallery/gid#pid
            // http://nickname.smugmug.com/photos/pid-D.jpg
            $sRoot = substr($sURL,0,strpos($sURL,'gallery'));
            $sTmp = substr($sURL,strpos($sURL,'gallery/')+8);
            list($gid,$pid) = explode('#',$sTmp);
            if( strpos($pid,'-') ) {
                $pid = substr($pid,0,strpos($pid,'-'));
            }
            $sURL = $sRoot . 'photos/' .$pid . '-O.jpg';
        }

        return $sURL;
    }

}

