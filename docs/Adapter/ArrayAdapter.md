# Rodas\Dotenvx\Adapter\ArrayAdapter _(class)_

[Rodas\Dotenvx](https://github.com/Marqitos/php-dotenvx/blob/main/docs/library.md)\Adapter > **ArrayAdapter**

Read or write de values on a array,
and with the ability to decrypt its contents

```mermaid
classDiagram
    ArrayAdapter <|-- DecryptableAdapterInterface
    ArrayAdapter <|-- AdapterInterface
    AdapterInterface <|-- ReaderInterface
    AdapterInterface <|-- WriterInterface
    namespace `Rodas\Dotenvx\Adapter` {
        class ArrayAdapter{ }
        class DecryptableAdapterInterface {
            <<Interface>>
            + array values
            + decrypt(keyProvider)
            + getEncryptedValues() array
            + isEncrypted(publicKey) string|false
            + replaceEncryptedValues(decryptedValues) bool
        }
    }
    namespace `Dotenv\Repository\Adapter` {
        class AdapterInterface{
            <<Interface>>
            + ::create()
        }
        class ReaderInterface {
            <<Interface>>
            + read(name) Option
        }
        class WriterInterface {
            <<Interface>>
            + delete(name) bool
            + write(name, value) bool
        }
    }
```

See:

- [DecryptableAdapterInterface](https://github.com/Marqitos/php-dotenvx/blob/main/docs/Adapter/DecryptableAdapterInterface.md) _(interface)_
