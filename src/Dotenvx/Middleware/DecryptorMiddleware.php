<?php
/**
 * This file is part of the Rodas\Dotenvx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rodas\Dotenvx
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Dotenvx\Middleware;

use Dotenv\Parser\Entry;
use Dotenv\Parser\Value;
use PhpOption\Option;
use SensitiveParameter;

use function array_unique;
use function call_user_func;
use function in_array;
use function is_string;
use function str_split;
use function substr;

class DecryptorMiddleware implements MiddlewareInterface {

    protected $decryptor;

    /**
     * Create a new instance of DecryptorMiddleware
     *
     * @param  callable(string, array):array $callback Callable with the signature `function(string $publicKey, array $encryptedValues): array`
     */
    public function __construct(callable $callback) {
        $this->decryptor = $callback;
    }

# MiddlewareInterface Members
    /**
     * Process all entries
     *
     * @param Entry[] $entries The entries to process
     *
     * @return Entry[] The processed entries
     */
    public function process(array $entries): array {
        $publicKey = self::isEncrypted($entries);

        if (is_string($publicKey)) {
            $encryptedValues    = self::getEncryptedValues($entries);
            $decryptedValues    = call_user_func($this->decryptor, $publicKey, $encryptedValues);
            $entries            = self::replaceEncryptedValues($entries, $decryptedValues);
        }

        return $entries;
    }
# -- MiddlewareInterface Members

    /**
     * Return all encrypted values as base64 encoded strings
     *
     * @return array<string> All encrypted values as base64 encoded strings
     */
    public static function getEncryptedValues(#[SensitiveParameter]array $entries): array {
        $encryptedValues = [];
        // Find encrypted values
        foreach ($entries as $entry) {
            if ($entry->getValue()->isDefined()) {
                $chars = $entry->getValue()->get()->getChars();
            } else {
                continue;
            }
            if (is_string($chars) &&
                substr($chars, 0, 10) == 'encrypted:') {

                $encryptedValues[] = substr($chars, 10);
            }
        }
        return array_unique($encryptedValues);
    }

    /**
     * Return if contains encrypted values, and there is a public key
     *
     * @param  ?string              $publicKey (Optional) The public key used for encryption.
     * @return string|false                    The public key if encrypted values are found, otherwise false.
     * @throws RuntimeException                If there isn't public key when encrypted values exist.
     */
    public static function isEncrypted(#[SensitiveParameter]array $entries, ?string $publicKey = null): string|false {
        $hasEncryptedValues = false;
        // Find public key and encrypted values
        foreach ($entries as $entry) {
            if ($publicKey != null &&
                !empty($publicKey) &&
                $hasEncryptedValues) {

                return $publicKey;
            }
            if ($entry->getValue()->isDefined()) {

                $chars = $entry->getValue()->get()->getChars();
            } else {
                continue;
            }
            if ($entry->getName() == 'DOTENV_PUBLIC_KEY' &&
                is_string($chars) &&
                !empty($chars)) {

                $publicKey = $chars;
            } elseif (!$hasEncryptedValues &&
                      is_string($chars) &&
                      substr($chars, 0, 10) == 'encrypted:') {
                $hasEncryptedValues = true;
            }
        }

        if ($hasEncryptedValues) {
            if ($publicKey == null ||
                empty($publicKey)) {

                throw new RuntimeException('PUBLIC KEY not found');
            }
            return $publicKey;
        } else {
            return false;
        }
    }

    /**
     * Replace encrypted values with decrypted values
     *
     * @param  array<string, mixed> $values Decrypted values, encrypted values as keys.
     * @return array<Entry>                 Still contains encrypted values after replacement.
     */
    public static function replaceEncryptedValues(#[SensitiveParameter]array $entries, #[SensitiveParameter] array $decryptedValues): array {
        $result = [];
        // Find encrypted values
        foreach ($entries as $entry) {
            if ($entry->getValue()->isDefined()) {
                $value  = $entry->getValue()->get();
                $chars  = $value->getChars();
                if (is_string($chars) &&
                    substr($chars, 0, 10) == 'encrypted:') {

                    $encrypted = substr($chars, 10);
                    if (isset($decryptedValues[$encrypted])) {

                        $value = Value::blank();
                        foreach (str_split($decryptedValues[$encrypted]) as $token) {
                            $value = $value->append($token, $token === '$');
                        }
                        $result[] = new Entry(
                            $entry->getName(),
                            $value
                        );
                        continue;
                    }
                }
            }
            $result[] = $entry;
        }
        return $result;
    }
}
