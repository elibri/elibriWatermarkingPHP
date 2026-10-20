<?php

declare(strict_types=1);

use Elibri\Watermarking\Exception\ElibriException;
use Elibri\Watermarking\WatermarkingClient;

require_once __DIR__ . '/../vendor/autoload.php';

$client = new WatermarkingClient(
    '061df0c87cd0472a5e4e5708fd788246',
    'fbef4d3bbb30cbf502bbdb0f8ede3fc7'
);

try {
    foreach ($client->availableFiles() as $book) {
        printf(
            "%s - %s%s\n",
            $book['record_reference'] ?? '?',
            $book['title'] ?? '?',
            isset($book['available_date']) ? ' (premiera: ' . $book['available_date'] . ')' : ''
        );
    }
} catch (ElibriException $e) {
    fwrite(STDERR, 'Błąd API elibri: ' . $e->getMessage() . "\n");
    exit(1);
}
