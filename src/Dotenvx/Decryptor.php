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
use Rodas\Dotenvx\Adapter\ArrayAdapter;
use Rodas\Dotenvx\Adapter\ArrayMultiAdapter;
use Rodas\Dotenvx\Provider\KeyProviderInterface;
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

    /**
     * Decrypts a value using the provided private key and public key.
     *
     * @param  string               $encryptedValue The encrypted value to decrypt.
     * @param  KeyProviderInterface $keyProvider    Keys used for decryption.
     * @return string                               The decrypted value.
     * @throws Exception                            If the decryption fails.
     */
    public static function decrypt(string $encryptedValue, #[SensitiveParameter] KeyProviderInterface $keyProvider): string {
        if (substr($encryptedValue, 0, 10) == 'encrypted:') {
            $cipherText     = base64_decode(substr($encryptedValue, 10));
            $privateKey     = sodium_base642bin($keyProvider->privateKey,   SODIUM_BASE64_VARIANT_ORIGINAL);
            $publicKey      = sodium_base642bin($keyProvider->publicKey,    SODIUM_BASE64_VARIANT_ORIGINAL);
            $keyPair        = sodium_crypto_box_keypair_from_secretkey_and_publickey($privateKey, $publicKey);
            $plaintext      = sodium_crypto_box_seal_open($cipherText, $keyPair);
            if ($plaintext === false) {
                throw new Exception("Desencriptado fallido");
            }
            return $plaintext;
        }
        return $encryptedValue;
    }

    /**
     * Decrypt all encrypted values in an ArrayAdapter instance.
     *
     * @param  ArrayAdapter         $adapter     The ArrayAdapter instance containing the values to decrypt.
     * @param  KeyProviderInterface $keyProvider Keys used for decryption.
     * @return void
     */
    public static function decryptArrayAdapter(#[SensitiveParameter]ArrayAdapter $adapter, #[SensitiveParameter] KeyProviderInterface $keyProvider): void {
        foreach ($adapter->values as $key => $value) {
            if ($key == 'DOTENV_PUBLIC_KEY') {
                continue;
            }
            if (is_string($value) &&
                substr($value, 0, 10) == 'encrypted:') {

                $decryptedValue = self::decrypt($value, $keyProvider);
                $adapter->write($key, $decryptedValue);
            }
        }
    }

    /**
     * Decrypt all encrypted values in an ArrayMultiAdapter instance.
     *
     * @param  ArrayMultiAdapter    $adapter     The ArrayMultiAdapter instance containing the values to decrypt.
     * @param  KeyProviderInterface $keyProvider Keys used for decryption.
     * @param  array<string>        $xPath       The path to the values to decrypt within the ArrayMultiAdapter instance.
     * @return void
     */
    public static function decryptArrayMultiAdapter(#[SensitiveParameter]ArrayMultiAdapter $adapter, #[SensitiveParameter] KeyProviderInterface $keyProvider, array $xPath = []): void {
        $values     = $adapter->values;
        foreach ($xPath as $part) {
            $values = $values[$part];
        }
        foreach ($values as $key => $value) {
            if ($key == 'DOTENV_PUBLIC_KEY') {
                continue;
            }
            if (is_string($value) &&
                substr($value, 0, 10) == 'encrypted:') {

                $decryptedValue = self::decrypt($value, $keyProvider);
                $key = $adapter->getKey(array_merge($xPath, [$key]));
                $adapter->write($key, $decryptedValue);
            } elseif (is_array($value)) {
                self::decryptArrayMultiAdapter($adapter, $keyProvider, array_merge($xPath, [$key]));
            }
        }
    }

    /**
     * Encrypts a value using the provided public key.
     *
     * @param  string $value     The value to encrypt.
     * @param  string $publicKey The public key used for encryption.
     * @return string            The encrypted value.
     */
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
     */
    public static function cryptoBase64Encode(string $string) {
        return sodium_bin2base64($string, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * Converts a base64 encoded string into raw binary (constant-time mode).
     *
     * @param  string $string   Base64 string.
     * @return string           Decoded/raw binary string.
     */
    public static function cryptoBase64Decode(string $string) {
        return sodium_base642bin($string, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

}
