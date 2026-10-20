<?php

declare(strict_types=1);

use Elibri\Watermarking\Exception\ElibriException;
use Elibri\Watermarking\Format;
use Elibri\Watermarking\WatermarkingClient;

require_once __DIR__ . '/../vendor/autoload.php';

// Dostępy do API elibri.
$token = '061df0c87cd0472a5e4e5708fd788246';
$secret = 'fbef4d3bbb30cbf502bbdb0f8ede3fc7';

$bookRecordReference = '2a0ec1239e285ec46b11';

$client = new WatermarkingClient($token, $secret);

try {
    // Zleć watermarkowanie - jak najwcześniej, np. gdy klient opuszcza koszyk.
    $transId = $client->watermark(
        $bookRecordReference,
        [Format::EPUB, Format::MOBI],
        'Książka dla Wojtka Kowalskiego'
    );
    echo "transid: $transId\n";

    // Potwierdź transakcję - dopiero po zarejestrowaniu płatności.
    echo $client->deliver($transId), "\n";

    // Teraz czekaj na callback od elibri na zdefiniowany adres;
    // w callbacku znajdziesz linki do zwatermarkowanych plików.
} catch (ElibriException $e) {
    fwrite(STDERR, 'Błąd API elibri: ' . $e->getMessage() . "\n");
    exit(1);
}
