# PHP dotenvx

Plaintext `.env` files have been a major attack vector,
but they've also been undeniably useful.

[A secure dotenv (dotenvx.com)](https://dotenvx.com/)

> "Dotenvx: Reducing Secrets Risk with Cryptographic Separation
>
> Abstract. An ideal secrets solution would not only centralize secrets but also
> contain the fallout of a breach.
> While secrets managers offer centralized storage and distribution,
> their design creates a large blast radius,
> risking exposure of thousands or even millions of secrets.
> We propose a solution that reduces the blast radius by splitting secrets
> management into two distinct components:
> an encrypted secrets file and a separate decryption key.
>
> ..."
>
> [Read the whitepaper](https://github.com/Marqitos/php-dotenvx/blob/main/docs/dotenvx.pdf)

Dotenvx decrypts your `.env` files.
Using with cryptographic separation limiting their attack vector while
retaining their benefits.
Allowing use in small projects, on virtual servers, where it is not possible to
run an external startup script for the application.

> [!IMPORTANT]
> But for this to be effective, the decryption keys (private key) and the
application must be in separate environments, while maintaining security in each
environment.

<div align="center">

[![BSD 3-Clause License](https://img.shields.io/badge/license-BSD%203--Clause-brightgreen.svg)](https://github.com/Marqitos/php-dotenvx?tab=BSD-3-Clause-1-ov-file)
[![Latest Version](https://img.shields.io/github/release/marqitos/php-dotenvx.svg)](https://github.com/Marqitos/php-dotenvx/releases)
[![Run unit tests (PHPUnit)](https://github.com/Marqitos/php-dotenvx/actions/workflows/test-unit.yml/badge.svg)](https://github.com/Marqitos/php-dotenvx/actions/workflows/test-unit.yml)

![dotvenx](https://github.com/Marqitos/php-dotenvx/blob/main/docs/dotenvx-cover.svg)

</div>

This library also provides an adapter to dump the `.env` values ​​into
an array and a multi-level array.

## How to use

See [the documentation](https://github.com/Marqitos/php-dotenvx/blob/main/docs/quickstart.md).

---

Under the hood,
this is all implemented using the same public-key cryptography as [Bitcoin](https://en.bitcoin.it/wiki/Secp256k1).
