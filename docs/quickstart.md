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
    state "Load using decryptor" as loadDecryptor
    state "Get or use values" as use 
    [*] --> hasExternal
    hasExternal --> has_external
    has_external --> loadDecryptor: True
    has_external --> load: False
    load --> isEncrypted
    isEncrypted --> is_encrypted
    is_encrypted --> use: False
    is_encrypted --> private: True
    loadDecryptor --> use
    private --> decryptPrivate
    decryptPrivate --> use
    use --> [*]
```

```php
// Decryptor signature
function decrypt(string $publicKey, array $encryptedValues): array;

// Load using decryptor
$arrayAdapter       = new ArrayAdapter();
$repository         = RepositoryBuilder::createWithNoAdapters()
    ->addAdapter($arrayAdapter)
    ->make();
Dotenvx::create($repository, __DIR__)->loadEncrypted('decrypt');

// Get or use values
$options            = $arrayAdapter->values;
```
