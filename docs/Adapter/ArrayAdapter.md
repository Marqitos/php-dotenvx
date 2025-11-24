# Rodas\Dotenvx\Adapter\ArrayAdapter _(class)_

[Rodas\Dotenvx](https://github.com/Marqitos/php-dotenvx/blob/main/docs/library.md)\Adapter > **ArrayAdapter**

Read or write de values on a array,
and with the ability to decrypt its contents

```mermaid
classDiagram
    note for ArrayAdapter "Rodas\Dotenvx\Adapter namespace"
    note for AdapterInterface "Dotenv\Repository\Adapter namespace"
    ArrayAdapter <|-- DecryptableAdapterInterface
    ArrayAdapter <|-- AdapterInterface
    AdapterInterface <|-- ReaderInterface
    AdapterInterface <|-- WriterInterface
    class ArrayAdapter{ }
    class DecryptableAdapterInterface{
        array values
        ->decrypt()
        ->isEncrypted()
    }
    class AdapterInterface{
        ->create()
    }
    class ReaderInterface {
        ->read()
    }
    class WriterInterface {
        ->delete()
        ->write()
    }
```

See:

- [DecryptableAdapterInterface](https://github.com/Marqitos/php-dotenvx/blob/main/docs/Adapter/DecryptableAdapterInterface.md) _(interface)_
