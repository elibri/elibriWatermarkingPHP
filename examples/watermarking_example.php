<?php


$book_rr = "<podaj record reference książki>";

//dostępy do API eLibri
$key = "<podaj publiczny klucz elibri>";
$secret = "<podaj tajny klucz elibri>";

require_once(dirname(__FILE__) . '/elibriWatermarkingPHP.php');

$client = new ElibriWatermarkingClient($key, $secret);

//zleć watermarkowanie
$transid = $client->watermark($book_rr, "mobi,epub", "Książka dla Wojtka Kowalskiego", "Wojtek Kowalski", "178.42.78.98"); 
print "transid: $transid\n";

//potwierdzaj tranzakcję oraz dostarcz plik do bucketu S3 przypisanego odbiorcy
print $client->deliver($transid);

//czekaj na callback od elibri na zdefiniowany adres,
//a wtedy ściągnij plik, wykasuj go z S3 i udostęnij go klientowi

?>
