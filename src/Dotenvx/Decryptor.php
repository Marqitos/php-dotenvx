<?php
/**
 * This file is part of the Rodas\Doventx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rodas\Doventx
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Dotenvx;

use Exception;
use RuntimeException;

use function function_exists;
use function sodium_base642bin;
use function sodium_bin2base64;
use function sodium_crypto_box_keypair;
use function sodium_crypto_box_keypair_from_secretkey_and_publickey;
use function sodium_crypto_box_publickey;
use function sodium_crypto_box_seal;
use function sodium_crypto_box_seal_open;
use function sodium_crypto_box_secretkey;

use const SODIUM_BASE64_VARIANT_ORIGINAL;

/**
 * Provide Montgomery curve, Curve25519, encryption functions. (Usually abbreviated as X25519)
 */
class Decryptor {
    private function __construct() {
        // Is a Singleton class
    }

    /**
     * Return a private key and a public key pair of X25519 in base64
     *
     * @return array<string>    [privateKey, publicKey] Keys base64 encoded.
     * @throws RuntimeException If the Sodium extension is not available, and the polyfill can't be loaded.
     */
    public static function createKeyPair() {
        $keyPair = sodium_crypto_box_keypair();
        $privateKey = sodium_crypto_box_secretkey($keyPair);
        $publicKey  = sodium_crypto_box_publickey($keyPair);
        return [
            sodium_bin2base64($privateKey, SODIUM_BASE64_VARIANT_ORIGINAL),
            sodium_bin2base64($publicKey, SODIUM_BASE64_VARIANT_ORIGINAL)
        ];
    }

    public static function decrypt(string $encryptedValue, #[SensitiveParameter] string $privateKey, string $publicKey): string {
        if (substr($encryptedValue, 0, 10) == 'encrypted:') {
            $cipherText     = base64_decode(substr($encryptedValue, 10));
            $privateKeyBin  = sodium_base642bin($privateKey,    SODIUM_BASE64_VARIANT_ORIGINAL);
            $publicKeyBin   = sodium_base642bin($publicKey,     SODIUM_BASE64_VARIANT_ORIGINAL);
            $keyPair        = sodium_crypto_box_keypair_from_secretkey_and_publickey($privateKeyBin, $publicKeyBin);
            $plaintext      = sodium_crypto_box_seal_open($cipherText, $keyPair);
            if ($plaintext === false) {
                throw new Exception("Desencriptado fallido");
            }
            return $plaintext;
        }
        return $encryptedValue;
    }

    public static function encrypt(#[SensitiveParameter] string $value, string $publicKey): string {
        $publicKeyBin   = sodium_base642bin($publicKey, SODIUM_BASE64_VARIANT_ORIGINAL);
        $cipherText     = sodium_crypto_box_seal($value, $publicKeyBin);
        return 'encrypted:' . base64_encode($cipherText);
    }

    /**
     * Converts a raw binary string into a base64-encoded string (constant-time mode).
     *
     * @param  string $string   Decoded/raw binary string.
     * @return string           Base64 string.
     * @throws RuntimeException If the Sodium extension is not available, and the polyfill can't be loaded.
     */
    public static function cryptoBase64Encode(string $string) {
        return sodium_bin2base64($string, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * Converts a base64 encoded string into raw binary (constant-time mode).
     *
     * @param  string $string   Base64 string.
     * @return string           Decoded/raw binary string.
     * @throws RuntimeException If the Sodium extension is not available, and the polyfill can't be loaded.
     */
    public static function cryptoBase64Decode(string $string) {
        return sodium_base642bin($string, SODIUM_BASE64_VARIANT_ORIGINAL);
    }
    
}
