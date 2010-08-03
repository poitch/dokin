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


class SimpleDB extends AWS {
    protected $sURL = 'https://sdb.amazonaws.com/';
    protected $sVersion = '2007-11-07';

    static protected $aInstances = array();
    static protected $aInstance = null;
    public static function get_instance($sKey,$sSecret) {
        if ($sKey && !self::$aInstances[$sKey]) {
            self::$aInstances[$sKey] = new SimpleDB($sKey,$sSecret);
            if (!self::$aInstance) {
                self::$aInstance = self::$aInstances[$sKey];
            }
        }
        if ($sKey) {
            return self::$aInstances[$sKey];
        } else {
            return self::$aInstance;
        }
    }

    public function CreateDomain($sDomainName) 
    {
        $hParams = array();
        $hParams['Action'] = 'CreateDomain';
        $hParams['DomainName'] = $sDomainName;
        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        return $oXML;
    }

    public function DeleteDomain($sDomainName) 
    {
        $hParams = array();
        $hParams['Action'] = 'DeleteDomain';
        $hParams['DomainName'] = $sDomainName;
        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        return $oXML;
    }

    public function ListDomains($iMax = null, $sNext = null ) 
    {
        $hParams = array();
        $hParams['Action'] = 'ListDomains';
        if ($iMax !== null) {
            $hParams['MaxNumberOfDomains'] = $iMax;
        }
        if ($sNext !== null) {
            $hParams['NextToken'] = $sNext;
        }
        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $aDomains = array();
        foreach ($oXML->ListDomainsResult->DomainName as $oDomain) {
            $aDomains[] = (string)$oDomain;
        }
        $sNext = (string)$oXML->ListDomainsResult->NextToken;

        return array('domains' => $aDomains, 'next' => $sNext);
    }

    public function PutAttributes($sDomainName,$sItemName,$hAttributes) 
    {
        $hParams = array();
        $hParams['Action']     = 'PutAttributes';
        $hParams['ItemName']   = $sItemName;
        $hParams['DomainName'] = $sDomainName;

        $i = 0;
        foreach ($hAttributes as $sKey => $mValue) {
            if (is_array($mValue) && $mValue['replace']) {
                $hParams['Attribute.'.$i.'.Name']  = $sKey;
                $hParams['Attribute.'.$i.'.Value'] = $mValue['value'];
                $hParams['Attribute.'.$i.'.Replace'] = 'true';
                $i++;
            } else if (is_array($mValue)) {
                foreach ($mValue as $val) {
                    $hParams['Attribute.'.$i.'.Name']  = $sKey;
                    $hParams['Attribute.'.$i.'.Value'] = $val;
                    $i++;
                }
            } else {
                $hParams['Attribute.'.$i.'.Name']  = $sKey;
                $hParams['Attribute.'.$i.'.Value'] = $mValue;
                $i++;
            }
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        return $oXML;
    }

    public function DeleteAttributes($sDomainName,$sItemName) 
    {
        $hParams = array();
        $hParams['Action']     = 'DeleteAttributes';
        $hParams['ItemName']   = $sItemName;
        $hParams['DomainName'] = $sDomainName;
        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        return $oXML;
    }

    public function GetAttributes($sDomainName,$sItemName) 
    {
        $hParams = array();
        $hParams['Action']     = 'GetAttributes';
        $hParams['ItemName']   = $sItemName;
        $hParams['DomainName'] = $sDomainName;
        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $hItem = array();
        if ($oXML->GetAttributesResult->Attribute) {
            foreach ($oXML->GetAttributesResult->Attribute as $oAttribute) {
                $hItem[(string)$oAttribute->Name] = (string)$oAttribute->Value;
            }
        }

        return $hItem;
    }

    public function Query($sDomainName,$sQuery,$iMax = null, $sNext = null) 
    {
        $hParams = array();
        $hParams['Action'] = 'Query';
        $hParams['DomainName'] = $sDomainName;
        $hParams['QueryExpression'] = $sQuery;

        if ($iMax !== null) {
            $hParams['MaxNumberOfDomains'] = $iMax;
        }
        if ($sNext !== null) {
            $hParams['NextToken'] = $sNext;
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $aItems = array();
        if ($oXML->QueryResult->ItemName) {
            foreach ($oXML->QueryResult->ItemName as $oName) {
                $aItems[] = (string)$oName;
            }
        }
        $sNext = (string)$oXML->QueryResult->NextToken;

        return array('items' => $aItems, 'next' =>$sNext);
    }


}

