# Rodas\Dotenvx library usage

[Library files and description](https://github.com/Marqitos/php-dotenvx/blob/main/docs/library.md)

## Example

```mermaid
stateDiagram-v2
    state "Load data" as load
    state "Is encrypted?" as isEncrypted
    state is_encrypted <<choice>>
    state "Has decryptor in other environment?" as hasExternal
    state has_external <<choice>>
    state "Get private key" as private
    state "Decrypt values with private key" as decryptPrivate
    state "Get encrypted values" as getValues
    state "Replace encrypted values" as replace
    state "Decrypt values" as decrypt
    state "Get or use values" as use 
    [*] --> load
    load --> isEncrypted
    isEncrypted --> is_encrypted
    is_encrypted --> use: False
    is_encrypted --> hasExternal: True
    hasExternal --> has_external
    has_external --> getValues: True
    has_external --> private: False
    getValues --> decrypt
    decrypt --> replace
    replace --> use
    private --> decryptPrivate
    decryptPrivate --> use
    use --> [*]
```

```php
// Load data
$arrayAdapter       = new ArrayAdapter();
$repository         = RepositoryBuilder::createWithNoAdapters()
    ->addAdapter($arrayAdapter)
    ->make();
Dotenv::create($repository, __DIR__)->load();

// Is encrypted?
$publicKey          = $arrayAdapter->isEncrypted();
$arrayAdapter->delete('DOTENV_PUBLIC_KEY');
if (is_string($publicKey)) {

    // Find private key
    // Don't make this in production
    $repository         = RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(ArrayAdapter::class)
        ->make();
    $privateData        = Dotenv::create($repository, __DIR__, '.env.key')->load();
    $privateKey         = $privateData['DOTENV_PRIVATE_KEY'];

    // Decrypt data
    $staticKeyProvider  = new StaticKeyProvider($publicKey, $privateKey);
    $arrayAdapter->decrypt($staticKeyProvider);

    unset($repository, $privateData, $privateKey, $staticKeyProvider) 
}

// Get or use values
$options            = $arrayAdapter->values;
```
