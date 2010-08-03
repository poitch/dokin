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

class Request
{
    var $SubscriptionId;
    var $AssociateTag;
}

class ItemSearch  extends Request
{
    var $Request;
}

class ItemSearchRequest
{
    var $Operation = 'ItemSearch';
    var $SearchIndex;
    var $Artist;
    var $Title;
    var $ResponseGroup;
}

class ItemLookup extends Request
{
    var $Request;
}

class ItemLookupRequest
{
    var $Operation = 'ItemLookup';
    var $ItemId;
    var $ResponseGroup;
}

class AWSECS
{
    public static $sURL = 'http://webservices.amazon.com/AWSECommerceService/';
    public static $sWSDL = 'AWSECommerceService.wsdl';

    public static function by_asin($sASIN)
    {
        $client = new SoapClient(self::$sURL.self::$sWSDL);
        $request = new ItemLookupRequest();
        $request->ItemId   = $sASIN;
        $request->ResponseGroup = array('Large');

        $a = new ItemLookup();
        $a->SubscriptionId = '1KQKYN1NNCQDXTQ5WZG2';
        $a->AssociateTag   = 'webservices-20';
        $a->Request         = array($request);

        try {
            $result = $client->ItemLookup($a);
            return $result;
        } catch(SoapFault $e) {
            _ERROR("SOAP Error : ".$e->GetMessage());
        } 
    }

    public static function search_title($sSearchIndex, $sTitle, $iPageIndex = null)
    {
        $client = new SoapClient(self::$sURL.self::$sWSDL);
        $request = new ItemSearchRequest();
        $request->SearchIndex   = $sSearchIndex;
        $request->Title         = $sTitle;
        $request->ResponseGroup = array('Large');
        if ($iPageIndex !== null) {
            $request->ItemPage      = $iPageIndex;
        }

        $a = new ItemSearch();
        $a->SubscriptionId = '1KQKYN1NNCQDXTQ5WZG2';
        $a->AssociateTag   = 'webservices-20';
        $a->Request         = array($request);

        try {
            $result = $client->ItemSearch($a);
            return $result;
        } catch(SoapFault $e) {
            _ERROR("SOAP Error : ".$e->GetMessage());
        } 
    }

    public static function search_software($sTitle, $iPageIndex = null)
    {
        $client = new SoapClient(self::$sURL.self::$sWSDL);
        $request = new ItemSearchRequest();
        $request->SearchIndex   = 'Software';
        $request->Title         = $sTitle;
        $request->ResponseGroup = array('Large');
        if ($iPageIndex !== null) {
            $request->ItemPage      = $iPageIndex;
        }

        $a = new ItemSearch();
        $a->SubscriptionId = '1KQKYN1NNCQDXTQ5WZG2';
        $a->AssociateTag   = 'webservices-20';
        $a->Request         = array($request);

        try {
            $result = $client->ItemSearch($a);
            return $result;
        } catch(SoapFault $e) {
            _ERROR("SOAP Error : ".$e->GetMessage());
        } 
    }

    public static function get_music_artwork($sArtist,$sAlbum)
    {
        $client = new SoapClient("http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl");
        do {
            $request = new ItemSearchRequest();
            $request->SearchIndex   = 'Music';
            $request->Artist        = $sArtist;
            $request->Title         = $sAlbum;
            $request->ResponseGroup = array('Images', 'ItemAttributes', 'Tracks');
            $request->ItemPage      = $PageIndex;

            $a = new ItemSearch();
            $a->SubscriptionId = '1KQKYN1NNCQDXTQ5WZG2';
            $a->AssociateTag   = 'webservices-20';
            $a->Request         = array($request);

            try {
                $result = $client->ItemSearch($a);
                $Pages[$PageIndex] = &$result->Items->Item;
                $NbPages = $result->Items->TotalPages;

                if (isset($result->Items->Item)) {
                    foreach ($result->Items->Item as $N => $item) {
                        $Coef = 0;
                        $a = strtolower($sAlbum);
                        $b = strtolower(utf8_decode($item->ItemAttributes->Title));
                        similar_text($a,$b,&$Coef);
                        if ($Coef==100) {
                            // Found It
                            $hRes = array();
                            $hRes['ASIN']   = $item->ASIN;
                            $hRes['URL']    = urldecode($item->DetailPageURL);
                            $hRes['image']['small']  = $item->SmallImage->URL;
                            $hRes['image']['medium'] = $item->MediumImage->URL;
                            $hRes['image']['large']  = $item->LargeImage->URL;
                            return $hRes;
                        }
                    }
                }
            } catch(SoapFault $e) {
                _ERROR("SOAP Error : ".$e->GetMessage());
            } 
            $PageIndex++;
        } while ($PageIndex <= $NbPages);
    }
}

