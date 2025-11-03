# PHP dotenvx

Plaintext `.env` files have been a major attack vector, but they've also been undeniably useful.

Dotenvx dencrypts your `.env` files.
Using with cryptographic separation limiting their attack vector while retaining their benefits.
Allowing use in small projects, on virtual servers, where it is not possible to run an external startup script for the application.

> [!IMPORTANT]
> But for this to be effective, the encryption keys and the application must be in separate environments, while maintaining security in each environment.

This library also provides an adapter to dump the .env values ​​into a multi-level array.
