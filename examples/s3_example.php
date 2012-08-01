<?php


$transid = "<podaj numer tranzakcji>";
$key = "<publiczny klucz do bucketu S3>";
$secret = "<prywatny klucz do bucketu S3>";
$bucket = "<nazwa bucketu na S3>";
$destination = "<nazwa katalogu w którym zostanie nagrany plik>";

//http://aws.amazon.com/sdkforphp/ or http://pear.amazonwebservices.com/
//DOC: http://docs.amazonwebservices.com/AWSSDKforPHP/latest/index.html#i=AmazonS3
require_once 'AWSSDKforPHP/sdk.class.php';


//ustaw dane dostępowe do S3
CFCredentials::set(array(
  'development' => array(
    'key' => $key,
    'secret' => $secret,
    'default_cache_config' => '',
    'certificate_authority' => false
   ),
  '@default' => 'development'
));

$keyname = $transid . ".mobi";
$filepath = "$destination/$keyname";

//initializuj obiekt odpowiedzialny za komunikację z amazonem
$s3 = new AmazonS3();

//ściągaj plik 
$response = $s3->get_object(
                    $bucket,
                    $keyname,
                    array('fileDownload'=> $filepath));
print $response->isOK() . "\n";

//wykasuj go z S3
$reponse = $s3->delete_object($bucket, $keyname);
print $response->isOK() . "\n";


?>
