<?php
require_once 'S3.php';

$sKey = '1M1KX4J98HAWW36MFNG2';
$sSecret = 'nZBOWSVrflA1rkRy6AyeCkiafUVHlCBwT3PG6wJr';

$oS3 = new S3($sKey, $sSecret);
$aBuckets = $oS3->bucketsGet();
//print_r($aBuckets);
if ($oS3->bucketExists('jpoichet-backup')) {
    print "Deleting\n";
    if (!$oS3->bucketDelete('jpoichet-backup')) {
        print "Failed\n";
    }
}
if (!$oS3->bucketCreate('jpoichet-backup')) {
    print "Failed to create\n";
}

//print_r($oS3->bucketGetRequestPayment('jpoichet-backup'));
//print_r($oS3->bucketLocation('jpoichet-backup'));

//print_r($oS3->objectPut('jpoichet-backup', 'test.css', 'body, html { padding: 0; margin: 0}', true));
print_r($oS3->objectUpload('jpoichet-backup', 'test.css', 'test.css', true));

