<?php


$book_rr = "2a0ec1239e285ec46b11";

//dostępy do API eLibri
$key = "061df0c87cd0472a5e4e5708fd788246";
$secret = "fbef4d3bbb30cbf502bbdb0f8ede3fc7";

require_once(dirname(__FILE__) . '/../elibriWatermarkingPHP.php');

print "1\n";
$client = new ElibriWatermarkingClient($key, $secret);

print "2\n";
//zleć watermarkowanie
$transid = $client->watermark($book_rr, "mobi,epub", "Książka dla Wojtka Kowalskiego");
print "transid: $transid\n";

//potwierdzaj transakcję
print $client->deliver($transid);

//czekaj na callback od elibri na zdefiniowany adres, w callbacku znajdziesz linki do zwatermarkowanych plików

?>
