<?php

// What Symfony's and Composer's preload scripts do: load the class so OPcache
// keeps it in shared memory for every later request.
require __DIR__ . '/autoload.php';

class_exists(PreloadRepro\Client::class);
