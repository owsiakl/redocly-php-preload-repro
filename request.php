<?php

require __DIR__ . '/autoload.php';

printf("Client class already loaded: %s\n", class_exists(PreloadRepro\Client::class, false) ? 'yes (preloaded)' : 'no');

$client = new PreloadRepro\Client(new PreloadRepro\Config());

try {
    $client->getPet('1');
} catch (Throwable $e) {
    // Nothing listens on 127.0.0.1:9, so a working client fails with a
    // connection error. A broken one fails before it sends the request.
    printf("%s: %s\n", $e::class, $e->getMessage());
}
