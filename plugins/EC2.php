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

require_once 'HMAC.php';

class EC2 
{
    protected $sURL = 'https://ec2.amazonaws.com/';
    protected $sVersion = '2008-02-01';

    protected $sKey;
    protected $sSecret;
    protected $sOwner;

    public function __construct($sKey,$sSecret) 
    {
        $this->sKey = $sKey;
        $this->sSecret = $sSecret;
    }

    public function RegisterImage($sImageLocation) 
    {
        if(empty($sImageLocation)) {
            throw new Exception('Missing required parameter');
        }
        $hParams = array();
        $hParams['Action'] = 'RegisterImage';
        $hParams['ImageLocation'] = $sImageLocation;
        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        return (string)$oXML->imageId;
    }

    public function DescribeImages($aImageIds = array(), $aOwners = array(), $aExecutables = array()) 
    {
        $hParams = array();
        $hParams['Action'] = 'DescribeImages';
        if( sizeof($aImageIds) ) {
            for($i = 0; $i < sizeof($aImageIds); $i++ ) {
                $hParams['ImageId.'.($i+1)] = $aImageIds[$i];
            }
        }
        if( sizeof($aOwners) ) {
            for($i = 0; $i < sizeof($aOwners); $i++ ) {
                $hParams['Owner.'.($i+1)] = $aOwners[$i];
            }
        }
        if( sizeof($aExecutables) ) {
            for($i = 0; $i < sizeof($aExecutables); $i++ ) {
                $hParams['ExecutableBy.'.($i+1)] = $aExecutables[$i];
            }
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        $aImages = array();
        foreach( $oXML->imagesSet->item as $oItem ) {
            $hImage = array();
            $hImage['imageId'] = (string)$oItem->imageId;
            $hImage['imageLocation'] = (string)$oItem->imageLocation;
            $hImage['imageState'] = (string)$oItem->imageState;
            $hImage['imageOwnerId'] = (int)$oItem->imageOwnerId;
            $hImage['isPublic'] = ((string)$oItem->isPublic == 'true') ? true : false;
            $aImages[] = $hImage;
        }
        return $aImages;
    }

    public function DeregisterImage($sImageId) 
    {
        if(empty($sImageId)) {
            throw new Exception('Missing required parameter');
        }
        $hParams = array();
        $hParams['Action'] = 'DeregisterImage';
        $hParams['ImageId'] = $sImageId;
        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        return ((string)$oXML->return) == 'true' ? true : false;
    }

    public function RunInstances($sImageId,$iMinCount=1,$iMaxCount=1,$sKeyName=null,$aGroups=array(),$sUserData=null,$sAddressingType=null,$sInstanceType=null) 
    {
        if(empty($sImageId)) {
            throw new Exception('Missing required parameter');
        }
        $hParams = array();
        $hParams['Action'] = 'RunInstances';
        $hParams['ImageId'] = $sImageId;
        $hParams['MinCount'] = $iMinCount;
        $hParams['MaxCount'] = $iMaxCount;
        if(!empty($sKeyName)) {
            $hParams['KeyName'] = $sKeyName;
        }
        if(is_array($aGroups) && sizeof($aGroups)) {
            for($i = 0; $i < sizeof($aGroups); $i++ ) {
                $hParams['SecurityGroup.'.($i+1)] = $aGroups[$i];
            }
        }
        if(!empty($sUserData)) {
            $hParams['UserData'] = base64_encode($sUserData);
        }
        if(!empty($sAddressingType)) {
            // public or direct
            $hParams['AddressingType'] = $sAddressingType;
        }
        if(!empty($sInstanceType)) {
            // m1.small, m1.large, m1.xlarge
            $hParams['InstanceType'] = $sInstanceType;
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $hResult = array();
        $hResult['id'] = (string)$oXML->reservationId;
        $hResult['owner'] = (int)$oXML->ownerId;
        $hResult['groups'] = array();
        foreach( $oXML->groupSet->item as $oItem ) {
            $hResult['groups'][] = $oItem->groupId;
        }
        $hResult['instances'] = array();
        foreach( $oXML->instancesSet->item as $oItem ) {
            $hInstance = array();
            $hInstance['id'] = (string)$oItem->instanceId;
            $hInstance['image'] = (string)$oItem->imageId;
            $hInstance['state']['code'] = (int)$oItem->instanceState->code;
            $hInstance['state']['name'] = (string)$oItem->instanceState->name;
            $hInstance['dns']['private'] = (string)$oItem->privateDnsName;
            $hInstance['dns']['public'] = (string)$oItem->dnsName;
            $hInstance['reason'] = (string)$oItem->reason;
            $hInstance['key'] = (string)$oItem->keyName;
            $hInstance['index'] = (string)$oItem->amiLaunchIndex;
            $hInstance['products'] = (string)$oItem->productCodes;
            $hInstance['type'] = (string)$oItem->instanceType;
            $hInstance['time'] = (string)$oItem->launchTime;
            $hResult['instances'][] = $hInstance;
        }

        return $hResult;
    }

    public function TerminateInstances($mImages) 
    {
        if(empty($mImages)) {
            throw new Exception('Missing required parameter');
        }

        $hParams = array();
        $hParams['Action'] = 'TerminateInstances';

        if( is_string($mImages) ) {
            $hParams['InstanceId.1'] = $mImages;
        } else if( is_array($mImages) ) {
            for($i = 0; $i < sizeof($mImages); $i++) {
                $hParams['InstanceId'.($i+1)] = $mImages[$i];
            }
        } else {
            throw new Exception('Invalid parameter');
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $hResult = array();
        foreach( $oXML->instancesSet->item as $oItem ) {
            $hInstance = array();
            $hInstance['id'] = (string)$oItem->instanceId;
            $hInstance['shutdown']['code'] = (int)$oItem->shutdownState->code;
            $hInstance['shutdown']['name'] = (string)$oItem->shutdownState->name;
            $hInstance['previous']['code'] = (int)$oItem->previousState->code;
            $hInstance['previous']['name'] = (string)$oItem->previousState->name;
            $hResult[] = $hInstance;
        }
        return $hResult;

    }

    public function RebootInstances($mImages) 
    {
        if(empty($mImages)) {
            throw new Exception('Missing required parameter');
        }

        $hParams = array();
        $hParams['Action'] = 'RebootInstances';

        if( is_string($mImages) ) {
            $hParams['InstanceId.1'] = $mImages;
        } else if( is_array($mImages) ) {
            for($i = 0; $i < sizeof($mImages); $i++) {
                $hParams['InstanceId'.($i+1)] = $mImages[$i];
            }
        } else {
            throw new Exception('Invalid parameter');
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $hResult = array();
        foreach( $oXML->instancesSet->item as $oItem ) {
            $hInstance = array();
            $hInstance['id'] = (string)$oItem->instanceId;
            $hInstance['shutdown']['code'] = (int)$oItem->shutdownState->code;
            $hInstance['shutdown']['name'] = (string)$oItem->shutdownState->name;
            $hInstance['previous']['code'] = (int)$oItem->previousState->code;
            $hInstance['previous']['name'] = (string)$oItem->previousState->name;
            $hResult[] = $hInstance;
        }
        return $hResult;

    }

    public function DescribeInstances($aInstances = array()) 
    {
        $hParams = array();
        $hParams['Action'] = 'DescribeInstances';
        if( sizeof($aInstances) ) {
            for($i = 0; $i < sizeof($aInstances); $i++ ) {
                $hParams['InstanceId.'.($i+1)] = $aInstances[$i];
            }
        }
        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        $hResult = array();
        foreach( $oXML->reservationSet->item as $oItem ) {
            $hReservation = array();
            $hReservation['id'] = (string)$oItem->reservationId;
            $hReservation['owner'] = (string)$oItem->ownerId;
            $hReservation['groups'] = array();
            foreach( $oItem->groupSet->item as $oGroup ) {
                $hReservation['groups'][] = (string)$oGroup->groupId;
            }
            $hReservation['instances'] = array();
            foreach( $oItem->instancesSet->item as $oInstance ) {
                $hInstance = array();
                $hInstance['id'] = (string)$oInstance->instanceId;
                $hInstance['image'] = (string)$oInstance->imageId;
                $hInstance['state']['code'] = (int)$oInstance->instanceState->code;
                $hInstance['state']['name'] = (string)$oInstance->instanceState->name;
                $hInstance['dns']['private'] = (string)$oInstance->privateDnsName;
                $hInstance['dns']['public'] = (string)$oInstance->dnsName;
                $hInstance['reason'] = (string)$oInstance->reason;
                $hInstance['key'] = (string)$oInstance->keyName;
                $hInstance['index'] = (string)$oInstance->amiLaunchIndex;
                $hInstance['products'] = (string)$oInstance->productCodes;
                $hInstance['type'] = (string)$oInstance->instanceType;
                $hInstance['time'] = (string)$oInstance->launchTime;
                $hInstance['groups'] = $hReservation['groups'];
                $hReservation['instances'][] = $hInstance;
            }
            $hResult[] = $hReservation;
        }
        return $hResult;

    }

    public function CreateKeyPair($sName) 
    {
        if(empty($sName)) {
            throw new Exception('Missing required parameter');
        }
        $hParams = array();
        $hParams['Action'] = 'CreateKeyPair';
        $hParams['KeyName'] = $sName;

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $hResult = array();
        $hResult['name'] = (string)$oXML->keyName;
        $hResult['fingerprint'] = (string)$oXML->keyFingerprint;
        $hResult['key'] = (string)$oXML->keyMaterial;
        return $hResult;
    }

    public function DescribeKeyPairs($aNames = array()) 
    {
        $hParams = array();
        $hParams['Action'] = 'DescribeKeyPairs';
        if( sizeof($aNames) ) {
            for($i = 0; $i < sizeof($aNames); $i++ ) {
                $hParams['KeyName'.($i+1)] = $aNames[$i];
            }
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);
        $hResult = array();

        foreach( $oXML->keySet->item as $oItem ) {
            $hKey = array();
            $hKey['name'] = $oItem->keyName;
            $hKey['fingerprint'] = $oItem->keyFingerprint;
            $hResult[] = $hKey;
        }
        return $hResult;
    }

    public function DeleteKeyPair($sName) 
    {
        if( empty($sName) ) {
            throw new Exception('Missing required parameter');
        }

        $hParams = array();
        $hParams['Action'] = 'DeleteKeyPair';
        $hParams['KeyName'] = $sName;

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        return ((string)$oXML->return) == 'true' ? true: false;
    }

    public function ModifyImageAttribute($sImageId,$sAttribute,$sOperationType,$aUsers = array(), $aUserGroups = array(), $aProductCodes = array()) 
    {
        $hParams = array();
        $hParams['Action'] = 'ModifyImageAttribute';
        $hParams['ImageId'] = $sImageId;
        $hParams['Attribute'] = $sAttribute;
        if(!empty($sOperationType)) {
            $hParams['OperationType'] = $sOperationType;
        }
        if( sizeof($aUsers) ) {
            for($i = 0; $i < sizeof($aUsers); $i++ ) {
                $hParams['UserId.'.($i+1)] = $aUsers[$i];
            }
        }
        if( sizeof($aUserGroups) ) {
            for($i = 0; $i < sizeof($aUserGroups); $i++ ) {
                $hParams['UserGroup.'.($i+1)] = $aUserGroups[$i];
            }
        }
        if( sizeof($aProductCodes) ) {
            for($i = 0; $i < sizeof($aProductCodes); $i++ ) {
                $hParams['ProductCode.'.($i+1)] = $aProductCodes[$i];
            }
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        return ((string)$oXML->return) == 'true' ? true: false;
    }

    public function DescribeImageAttribute($sImageId,$sAttribute) 
    {
        $hParams = array();
        $hParams['Action'] = 'DescribeImageAttribute';
        $hParams['ImageId'] = $sImageId;
        $hParams['Attribute'] = $sAttribute;

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $aPermissions = array();
        foreach( $oXML->launchPermission->item as $oItem ) {
            $hPerm = array();
            if( $oItem->group ) {
                $hPerm['type'] = 'group';
                $hPerm['id'] = (string)$oItem->group;
            } else {
                $hPerm['type'] = 'user';
                $hPerm['id'] = (string)$oItem->userId;
            }
            $aPermissions[] = $hPerm;
        }
        $aProductCodes = array();
        foreach( $oXML->productCodes->item as $oItem ) {
            $aProductCodes[] = (string)$oItem->productCode;
        }

        return array( 'permissions' => $aPermissions, 'codes' => $aProductCodes);
    }

    public function DescribeSecurityGroups($aGroups = array() ) 
    {
        $hParams = array();
        $hParams['Action'] = 'DescribeSecurityGroups';
        if( is_array($aGroups) && sizeof($aGroups) ) {
            for($i = 0; $i < sizeof($aGroups); $i++ ) { 
                $hParams['GroupName.'+($i+1)] = $aGroups[$i];
            }
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $aGroups = array();
        foreach( $oXML->securityGroupInfo->item as $oItem ) {
            $hGroup = array();
            $hGroup['owner'] = (string)$oItem->ownerId;
            $hGroup['name'] = (string)$oItem->groupName;
            $hGroup['description'] = (string)$oItem->groupDescription;
            $hGroup['permissions'] = array();
            foreach( $oItem->ipPermissions->item as $oPerm ) {
                $hPerm = array();
                $hPerm['protocol'] = (string)$oPerm->ipProtocol;
                $hPerm['from'] = (string)$oPerm->fromPort;
                $hPerm['to'] = (string)$oPerm->toPort;
                $aPermGroups = array();
                foreach( $oPerm->groups->item as $oGroup ) {
                    $hPermGroup = array();
                    $hPermGroup['id'] = (string)$oGroup->userId;
                    $hPermGroup['name'] = (string)$oGroup->groupName;
                    $aPermGroups[] = $hPermGroup;
                }
                $hPerm['groups'] = $aPermGroups;

                $aRanges = array();
                foreach( $oPerm->ipRanges->item as $oRange ) {
                    $aRanges[] = (string)$oRange->cidrIp;
                }
                $hPerm['ranges'] = $aRanges;
                $hGroup['permissions'][] = $hPerm;
            }
            $aGroups[] = $hGroup;
        }

        return $aGroups;
    }

    public function CreateSecurityGroup($sName,$sDescription) 
    {
        $hParams = array();
        $hParams['Action'] = 'CreateSecurityGroup';
        $hParams['GroupName'] = $sName;
        $hParams['GroupDescription'] = $sDescription;

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        return ((string)$oXML->return) == 'true' ? true: false;
    }

    public function DeleteSecurityGroup($sName) 
    {
        $hParams = array();
        $hParams['Action'] = 'DeleteSecurityGroup';
        $hParams['GroupName'] = $sName;

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        return ((string)$oXML->return) == 'true' ? true: false;
    }

    public function AuthorizeSecurityGroupIngress($sName,$sGroupName,$sGroupOwner,$sProtocol,$sFrom,$sTo,$sIp) 
    {
        $hParams = array();
        $hParams['Action'] = 'AuthorizeSecurityGroupIngress';
        $hParams['GroupName'] = $sName;
        if( !empty($sGroupName) ) {
            $hParams['SourceSecurityGroupName'] = $sGroupName;
        }
        if( !empty($sGroupOwner) ) {
            $hParams['SourceSecurityGroupOwnerId'] = $sGroupOwner;
        }
        if( !empty($sProtocol) ) {
            $hParams['IpProtocol'] = $sProtocol;
        }
        if( !empty($sFrom) ) {
            $hParams['FromPort'] = $sFrom;
        }
        if( !empty($sTo) ) {
            $hParams['ToPort'] = $sTo;
        }
        if( !empty($sIp) ) {
            $hParams['CidrIp'] = $sIp;
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        return ((string)$oXML->return) == 'true' ? true: false;
    }

    public function RevokeSecurityGroupIngress($sName,$sGroupName,$sGroupOwner,$sProtocol,$sFrom,$sTo,$sIp) 
    {
        $hParams = array();
        $hParams['Action'] = 'RevokeSecurityGroupIngress';
        $hParams['GroupName'] = $sName;
        if( !empty($sGroupName) ) {
            $hParams['SourceSecurityGroupName'] = $sGroupName;
        }
        if( !empty($sGroupOwner) ) {
            $hParams['SourceSecurityGroupOwnerId'] = $sGroupOwner;
        }
        if( !empty($sProtocol) ) {
            $hParams['IpProtocol'] = $sProtocol;
        }
        if( !empty($sFrom) ) {
            $hParams['FromPort'] = $sFrom;
        }
        if( !empty($sTo) ) {
            $hParams['ToPort'] = $sTo;
        }
        if( !empty($sIp) ) {
            $hParams['CidrIp'] = $sIp;
        }

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        return ((string)$oXML->return) == 'true' ? true: false;
    }

    public function GetConsoleOutput($sInstance) 
    {
        $hParams = array();
        $hParams['Action'] = 'GetConsoleOutput';
        $hParams['InstanceId'] = $sInstance;

        $sXML = $this->do_method($hParams);
        $oXML = simplexml_load_string($sXML);

        $hResult = array();
        $hResult['instance'] = (string)$oXML->instanceId;
        $hResult['timestamp'] = (string)$oXML->timestamp;
        $hResult['output'] = (string)$oXML->output;

        return $hResult;
    }


    protected function do_method($hParams) 
    {
        $hParams = $this->sign($hParams);
        $sRequest = '?';
        foreach( $hParams as $key => $val ) {
            $sRequest .= $key . '=' . urlencode($val) . '&';
        }
        $sRequest = substr($sRequest,0,strlen($sRequest)-1);
        $sContent = file_get_contents($this->sURL.$sRequest);
        return $sContent;
    }

    protected function sign($hParams) 
    {
        $hParams['Version'] = $this->sVersion;
        $hParams['AWSAccessKeyId'] = $this->sKey;
        $hParams['Expires'] = date('c',time()+120);
        $hParams['SignatureVersion'] = 1;

        // TODO redo this part
        $hSignedParams = $hParams;
        $aKeys = array_keys($hSignedParams);
        foreach($aKeys as $i => $key ) {
            $aKeys[$i] = strtolower($key);
            $hMap[strtolower($key)] = $key;
        }
        sort($aKeys);
        $sString = '';
        foreach($aKeys as $key ) {
            $val = $hSignedParams[$hMap[$key]];
            $sString .= $hMap[$key] . $val;
        }
        $oCrypt = new Crypt_HMAC($this->sSecret,'sha1');
        $sSignature = $oCrypt->hash($sString);
        $sSignature = $this->hex2b64($sSignature);

        $hParams['Signature'] = $sSignature;
        return $hParams;

    }

    protected function hex2b64($str) 
    {
        $raw = '';
        for ($i=0; $i < strlen($str); $i+=2) {
            $raw .= chr(hexdec(substr($str, $i, 2)));
        }
        return base64_encode($raw);
    }

}

