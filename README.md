# PHP client breaks under OPcache preload

The PHP client from `redocly generate-client` declares its operation map as a
file-level constant and reads it from every `Client` method:

```php
const OPERATIONS = [
    'GetPet' => ['id' => 'GetPet', 'method' => 'GET', 'path' => '/pets/{id}', ...],
];

class Client
{
    public function getPet(string $id, ?array $headers = null): mixed
    {
        $op = OPERATIONS['GetPet'];
        // ...
```

With `opcache.preload`, which Symfony and FrankenPHP production setups use,
every client method throws:

```
Error: Undefined constant "PreloadRepro\OPERATIONS"
```

## Why

OPcache preload keeps classes and functions in shared memory but not
file-level constants. The constant exists only while the preload script runs.
On later requests `Client` already exists, so the autoloader (Composer
`classmap` or `psr-4`) never includes `client.php` again. The `const`
statement never runs and `OPERATIONS` stays undefined.

## Reproduce

Requires Node.js and PHP 8.1 or later with OPcache.

```sh
./run.sh            # latest @redocly/cli
./run.sh 2.48.0     # a specific version
```

The script generates a client from a one-operation `openapi.yaml` and calls
`getPet()` twice: once without preload, then with `opcache.preload`. Nothing
listens on the server URL, so a working client fails with a network error.

```
--- Without preload ---
Client class already loaded: no
PreloadRepro\TimeoutError: Request to http://127.0.0.1:9/pets/1 timed out after the configured timeout (3 attempt(s))

--- With opcache.preload ---
Client class already loaded: yes (preloaded)
Error: Undefined constant "PreloadRepro\OPERATIONS"
```

Confirmed with `@redocly/cli` 2.48.0 and 2.54.3 on PHP 8.5.10.

| File | Purpose |
| --- | --- |
| `openapi.yaml`, `redocly.yaml` | Minimal API and generator config |
| `autoload.php` | Loads `out/client.php` on first class use, like a Composer classmap |
| `preload.php` | `opcache.preload` script that loads `Client` |
| `request.php` | Simulates a request that calls `getPet()` |

## Suggested fix

Emit the map as a class constant and look it up through `self::`:

```php
class Client
{
    public const OPERATIONS = [
        'GetPet' => [...],
    ];

    public function getPet(string $id, ?array $headers = null): mixed
    {
        $op = self::OPERATIONS['GetPet'];
```

A class constant is stored with the class, so it survives preload. The
generated helpers such as `resolveAuth()` and `buildUrl()` are file-level
functions, which preload keeps, so they are not affected.
