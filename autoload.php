<?php

// Same as Composer's classmap autoloader: the file is only required when one
// of its classes is first used, and never if the class already exists.
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'PreloadRepro\\')) {
        require_once __DIR__ . '/out/client.php';
    }
});
