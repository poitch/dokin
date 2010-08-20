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

class Xss {
    static protected $xssAuto = 1;
    static protected $tagBlacklist = array('applet', 'body', 'bgsound', 'base', 'basefont',  'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer', 'layer', 'link', 'meta', 'name',  'script', 'title', 'xml');
    static protected $attrBlacklist = array('action', 'background', 'codebase', 'dynsrc', 'lowsrc'); 
    static protected $tagBlacklistExtra = array('/@import/i', '/<\s*script/i', '/<\s*iframe/i', '/xss/i','/alert\(/i');

    static public function process($source) 
    {
        if (is_array($source)) {
            foreach ($source as $key => $value) {
                if (is_string($value)) {
                    $source[$key] = self::remove(self::decode($value));
                }
            }
        } else if (is_string($source)) {
            $source = self::remove(self::decode($source));
        }
        return $source;
    }

    static protected function remove($source) 
    {
        $loopCounter=0;
        // provides nested-tag protection
        while ($source != self::filterTags($source)) {
            $source = self::filterTags($source);
            $loopCounter++;
        }

        $source = preg_replace('/<\s*img\s*\/>/i', '', $source);
        return preg_replace(self::$tagBlacklistExtra, '--TAG NOT ALLOWED--', $source);
    }       

    static protected function filterTags($source) 
    {
        // filter pass setup
        $preTag = NULL;
        $postTag = $source;
        // find initial tag's position
        $tagOpen_start = strpos($source, '<');
        // interate through string until no tags left
        while ($tagOpen_start !== FALSE) {
            // process tag interatively
            $preTag .= substr($postTag, 0, $tagOpen_start);
            $postTag = substr($postTag, $tagOpen_start);
            $fromTagOpen = substr($postTag, 1);
            // end of tag
            $tagOpen_end = strpos($fromTagOpen, '>');
            if ($tagOpen_end === false) break;
            // next start of tag (for nested tag assessment)
            $tagOpen_nested = strpos($fromTagOpen, '<');
            if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
                $preTag .= substr($postTag, 0, ($tagOpen_nested+1));
                $postTag = substr($postTag, ($tagOpen_nested+1));
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }
            $tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
            $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength = strlen($currentTag);
            if (!$tagOpen_end) {
                $preTag .= $postTag;
                $tagOpen_start = strpos($postTag, '<');
                $postTag = '';         
            }
            // iterate through tag finding attribute pairs - setup
            $tagLeft = $currentTag;
            $attrSet = array();
            $currentSpace = strpos($tagLeft, ' ');
            // is end tag
            if (substr($currentTag, 0, 1) == "/") {
                $isCloseTag = TRUE;
                list($tagName) = explode(' ', $currentTag);
                $tagName = substr($tagName, 1);
                // is start tag
            } else {
                $isCloseTag = FALSE;
                list($tagName) = explode(' ', $currentTag);
            }
            // excludes all "non-regular" tagnames OR no tagname OR remove if xssauto is on and tag is blacklisted
            if ((!preg_match("/^[a-z][a-z0-9]*$/i",$tagName)) || (!$tagName) || ((in_array(strtolower($tagName), self::$tagBlacklist)) && (self::$xssAuto))) {                             
                $postTag = substr($postTag, ($tagLength + 2));
                $tagOpen_start = strpos($postTag, '<');
                // don't append this tag
                continue;
            }
            // if this is a style tag do not allow @ sign
            if ($tagName && strtolower($tagName) == 'style') {
                $postTag = str_replace('@', '--TAG NOT ALLOWED--', $postTag);
            }
            // this while is needed to support attribute values with spaces in!
            while ($currentSpace !== FALSE) {
                $fromSpace = substr($tagLeft, ($currentSpace+1));

                //added by gwu: it was not friendly to something like <span style="color: #e0f">
                //- and converted it into <span style="color:">
                //remove spaces from attribute's sub attributes
                $fromSpace = preg_replace('/\s+\/$/', '', $fromSpace);
                $fromSpace = preg_replace(array('/\s*:\s*/', '/,\s*/', '/;\s*/'),
                        array(':',',',';'), $fromSpace);
                if (preg_match('/^style="[^"]+"[\/]*$/i', $fromSpace)) {
                    $fromSpace = preg_replace('/\s/', '---SPACE---', $fromSpace);
            }

            //remove spaces before and after "=" sign and replace one or more spaces with a single space
            $fromSpace = preg_replace('/=(\s*)(\S+)/', '=$2', preg_replace('/(\S+)(\s*)=/', '$1=', preg_replace('/(\s+)/', ' ', $fromSpace)));
            $nextSpace = strpos($fromSpace, ' ');
            $openQuotes = strpos($fromSpace, '"');
            $closeQuotes = strpos(substr($fromSpace, ($openQuotes+1)), '"') + $openQuotes + 1;
            // another equals exists
            if (strpos($fromSpace, '=') !== FALSE) {
                // opening and closing quotes exists
                if (($openQuotes !== FALSE) && (strpos(substr($fromSpace, ($openQuotes+1)), '"') !== FALSE) && (strpos($fromSpace, '=') - $openQuotes == 1))
                    $attr = substr($fromSpace, 0, ($closeQuotes+1));
                // one or neither exist
                else $attr = substr($fromSpace, 0, $nextSpace);
                // no more equals exist

            } else $attr = substr($fromSpace, 0, $nextSpace);

            // last attr pair
            if (!$attr) $attr = $fromSpace;
            // add to attribute pairs array
            $attrSet[] = $attr;
            // next inc
            $tagLeft = substr($fromSpace, strlen($attr));
            $currentSpace = strpos($tagLeft, ' ');
            }
            // find next tag's start
            $postTag = substr($postTag, ($tagLength + 2));
            $tagOpen_start = strpos($postTag, '<');                 
        }
        // append any code after end of tags
        $preTag .= $postTag;
        $preTag = preg_replace('/---SPACE---/', ' ', $preTag);
        return $preTag;
    }

    static protected function filterAttr($attrSet, $tagName = '') 
    {
        $newSet = array();
        // process attributes
        for ($i = 0; $i <count($attrSet); $i++) {
            // skip blank spaces in tag
            if (!$attrSet[$i]) continue;
            // EQUAL ENOCDE BEGIN -- enocde equal signs within quotes to avoid being exploded
            // convert tag to equal sign on initialization to avoid user manually inputting tag
            $attrSet[$i] = str_replace('--EQUAL_WITHIN_QUOTES--', '=', trim($attrSet[$i]));
            preg_match('/"([^"]+)"/',$attrSet[$i], $matches);
            $attrSet[$i] = preg_replace('/"([^"]+)"/', str_replace('=', '--EQUAL_WITHIN_QUOTES--', $matches[0]), $attrSet[$i]);
            // EQUAL ENCODE END                     
            // split into attr name and value
            $attrSubSet = explode('=', trim($attrSet[$i]));
            list($attrSubSet[0]) = explode(' ', $attrSubSet[0]);
            // removes all "non-regular" attr names AND also attr blacklisted
            if ((!eregi("^[a-z]*$",$attrSubSet[0])) || ((self::$xssAuto) && ((in_array(strtolower($attrSubSet[0]), self::$attrBlacklist)) || (substr($attrSubSet[0], 0, 2) == 'on'))))
                continue;
            // xss attr value filtering
            if ($attrSubSet[1]) {
                // convert equal tag back to sign
                $attrSubSet[1] = str_replace('--EQUAL_WITHIN_QUOTES--', '=',$attrSubSet[1]);
                // strips unicode, hex, etc
                $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
                // strip normal newline within attr value
                $attrSubSet[1] = preg_replace('/\s+/', '', $attrSubSet[1]);
                // strip double quotes
                $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
                // [requested feature] convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr value)
                if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'"))
                    $attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
                // strip slashes
                $attrSubSet[1] = stripslashes($attrSubSet[1]);
            }
            // auto strip attr's with "javascript:
            if (((strpos(strtolower($attrSubSet[1]), 'expression') !== false) &&
                (strtolower($attrSubSet[0]) == 'style')) ||
                (strpos(strtolower($attrSubSet[1]), 'javascript:') !== false) ||
                (strpos(strtolower($attrSubSet[1]), 'behaviour:') !== false) ||
                (strpos(strtolower($attrSubSet[1]), 'vbscript:') !== false) ||
                (strpos(strtolower($attrSubSet[1]), 'mocha:') !== false) ||
                (strpos(strtolower($attrSubSet[1]), 'livescript:') !== false) ||
                (strpos(strtolower($attrSubSet[1]), 'javascript') !== false) ||
                (strpos(strtolower($attrSubSet[0]), 'javascript') !== false)
               ) continue;
            // if this is an image tag, extra filtering for the src attribute
            if (strtolower($tagName) == 'img' && strtolower($attrSubSet['0']) == 'src') {
                $extensionValid = array('.BIFF', '.CGM', '.DPOF', '.EXIF', '.GIF', '.IMG', '.JPEG', '.JPG', '.MNG', '.PCX', '.PIC', '.PICT', '.PNG', '.RAW', '.TGA', '.WMF');
                if (strrpos($attrSubSet[1], '/') !== false && strpos($attrSubSet[1], '.', strrpos($attrSubSet[1], '/')) !== false
                        && in_array($extension = strtoupper(substr($attrSubSet[1], strpos($attrSubSet[1], '.', strrpos($attrSubSet[1], '/')))), $extensionValid)) {
                    // valid image source
                } else {
                    // invalid image source, remove all tag attributes
                    $newSet = array();
                    break;
                }
            }
        }
        return $newSet;
    }

    static protected function decode($source) 
    {
        // url decode
        $source = preg_replace('/&nbsp;/', '--space--', $source);
        $source = html_entity_decode($source, ENT_QUOTES, "ISO-8859-1");
        // convert decimal
        $source = preg_replace('/&#(\d+);/me',"chr(\\1)", $source);                             // decimal notation
        // convert hex
        $source = preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)", $source);   // hex notation
        $source = preg_replace('/--space--/', '&nbsp;', $source);
        return $source;
    }
}

