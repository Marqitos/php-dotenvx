<?php
/**
 * This file is part of the Rodas\Doventx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Dotenvx;

use Exception;
use RuntimeException;

use function function_exists;
use function sodium_bin2base64;
use function sodium_crypto_box_keypair;
use function sodium_crypto_box_keypair_from_secretkey_and_publickey;
use function sodium_crypto_box_publickey;
use function sodium_crypto_box_seal;
use function sodium_crypto_box_seal_open;
use function sodium_crypto_box_secretkey;

use const SODIUM_BASE64_VARIANT_ORIGINAL;

class Decryptor {
    private function __construct() {
        // Is a Singleton class
    }

    public static function createKeyPair() {
        if (function_exists('sodium_bin2base64') &&
            function_exists('sodium_crypto_box_keypair') &&
            function_exists('sodium_crypto_box_publickey') &&
            function_exists('sodium_crypto_box_secretkey')) {

            $keypair = sodium_crypto_box_keypair();
            $privateKey = sodium_crypto_box_secretkey($keypair);
            $publicKey  = sodium_crypto_box_publickey($keypair);
            return [
                sodium_bin2base64($privateKey, SODIUM_BASE64_VARIANT_ORIGINAL),
                sodium_bin2base64($publicKey, SODIUM_BASE64_VARIANT_ORIGINAL)
            ];
        }
        throw new RuntimeException("Sodium extension needed");
    }

    public static function decrypt(string $encryptedValue, #[SensitiveParameter] string $privateKey, string $publicKey): string {
        if (substr($encryptedValue, 0, 10) == 'encrypted:') {
            if (function_exists('sodium_base642bin') &&
                function_exists('sodium_crypto_box_keypair_from_secretkey_and_publickey') &&
                function_exists('sodium_crypto_box_seal_open')) {

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
            throw new RuntimeException("Sodium extension needed");
        }
        return $encryptedValue;
    }

    public static function encrypt(#[SensitiveParameter] string $value, string $publicKey): string {
        if (function_exists('sodium_base642bin') &&
            function_exists('sodium_crypto_box_keypair_from_secretkey_and_publickey') &&
            function_exists('sodium_crypto_box_seal')) {

            $publicKeyBin   = sodium_base642bin($publicKey, SODIUM_BASE64_VARIANT_ORIGINAL);
            $cipherText     = sodium_crypto_box_seal($value, $publicKeyBin);
            return 'encrypted:' . base64_encode($cipherText);
        }
        throw new RuntimeException("Sodium extension needed");
    }

    public static function crypto_base64_encode(string $string) {
        if (!function_exists('sodium_bin2base64')) {
            throw new RuntimeException("Sodium extension needed");
        }
        return sodium_bin2base64($string, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    public static function crypto_base64_decode(string $string) {
        if (!function_exists('sodium_base642bin')) {
            throw new RuntimeException("Sodium extension needed");
        }
        return sodium_base642bin($string, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    
}
