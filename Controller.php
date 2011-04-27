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

class Controller
{
    private $sMethod;
    private $hPageData = array();

    protected $sTemplate;
    protected $sLayout;

    private static $oInstance;

    public function __construct()
    {
        $this->sMethod         = $_SERVER['REQUEST_METHOD'];
        Controller::$oInstance = $this;
    }

    public static function get_instance()
    {
        return Controller::$oInstance;
    }

    public function request($sKey = null, $bXSSFilter = false)
    {
        if ($sKey) {
            if ($bXSSFilter) {
                return Validator::xss($_REQUEST[$sKey], false);
            } else {
                // Ignore COOKIES
                if (array_key_exists($sKey, $_COOKIE)) {
                    return null;
                } else {
                    return $_REQUEST[$sKey];
                }
            }
        } else {
            return $_REQUEST;
        }
    }

    public function hasRequest($sKey)
    {
        return array_key_exists($sKey, $_REQUEST);
    }

    public function server($sKey = null, $bXSSFilter = false) 
    {
        if ($sKey) {
            if ($bXSSFilter) {
                return Validator::xss($_SERVER[$sKey], false);
            } else {
                return $_SERVER[$sKey];
            }
        } else {
            return $_SERVER;
        }
    }

    public function cookie($sKey = null, $bXSSFilter = false)
    {
        if ($sKey) {
            if ($bXSSFilter) {
                return Validator::xss($_SERVER[$sKey], false);
            } else {
                return $_COOKIE[$sKey];
            }
        } else {
            return $_COOKIE;
        }
    }

    public function set($sKey,$sValue)
    {
        $this->hPageData[$sKey] = $sValue;
    }

    public function get($sKey = null)
    {
        if ($sKey) {
            return $this->hPageData[$sKey];
        } else {
            // Add object variables to data that was explicitaly set
            $aLocal = get_object_vars($this);
            $hData  = $this->hPageData;
            foreach ($aLocal as $sKey => $sValue) {
                if (!array_key_exists($sKey, $hData)) {
                    $hData[$sKey] = $sValue;
                }
            }
            return $hData;
        }
    }

    public function setTemplate($name)
    {
        $this->sTemplate = $name;
    }

    public function getTemplate()
    {
        return $this->sTemplate;
    }

    public function setLayout($name)
    {
        $this->sLayout = $name;
    }

    public function getLayout()
    {
        return $this->sLayout;
    }

    public function redirect($sURL, $bPermanent = false)
    {
        if ($bPermanent) {
            header('HTTP/1.1 301 Moved Permanently', true, 301);
        } else {
            header('HTTP/1.1 302 Moved', true, 302);
        }
        header('Location: '.$sURL);
        exit();
    }

    public function __get($sKey)
    {
        if (array_key_exists($sKey, $this->hPageData)) {
            return $this->hPageData[$sKey];
        } else if (array_key_exists($sKey, $_REQUEST)) {
            return $_REQUEST[$sKey];
        } else if (array_key_exists($sKey, $_COOKIE)) {
            return $_COOKIE[$sKey];
        }
        return null;
    }

    public function __set($sKey, $sValue)
    {
        $this->set($sKey, $sValue);
    }

    public function _render($sTemplatePath, $hData = array())
    { 
        extract($this->get());
        extract($hData);

        // Using a layout?
        $sLayout = $this->getLayout();
        $sLayout = $sLayout ? $sLayout : strtolower($sController);
        if (substr($sLayout, -4) == '.php') {
            $sLayoutPath = 'app/layouts/'.$sLayout;
        } else {
            $sLayoutPath = 'app/layouts/'.$sLayout.'.php';
        }

        if (!file_exists($sLayoutPath)) {
            $sLayoutPath = 'app/layouts/default.php';
            if (!file_exists($sLayoutPath)) {
                // WELL WE ONLY USE THE TEMPLATE
                include_once($sTemplatePath);
            } else {
                include_once($sLayoutPath);
            }
        } else {
            include_once($sLayoutPath);
        }
    }

}

