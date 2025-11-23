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

namespace Rodas\Dotenvx\Adapter;

use Dotenv\Repository\Adapter\AdapterInterface;
use PhpOption\{ None, Option, Some};
use Rodas\Dotenvx\Decryptor;
use Rodas\Dotenvx\Provider\KeyProviderInterface;
use SensitiveParameter;

use function array_merge;
use function count;
use function explode;

/**
 * Read or write de values on a multilevel array
 */
class ArrayMultiAdapter implements AdapterInterface, DecryptableAdapter {
# Fields
    /**
     * The variables and their values.
     *
     * @var array<string, mixed>
     */
    private array $variables;
    /**
     * Key to array level separator, for use with self::create()
     *
     * @var string
     */
    public static string $defaultSeparator = '.';
# -- Fields

# Properties
    /**
     * Char to split the name into keys
     *
     * @var string
     */
    public private(set) string $separator {
        set (string $value) {
            if (!empty($value)) {
                $this->separator = $value;
            }
        }
    }
# -- Properties

# Constructor
    /**
     * Create a new array adapter instance.
     *
     * @param string $separator Key to array level separator
     */
    public function __construct(string $separator) {
        $this->variables = [];
        if (empty($separator)) {
            $separator = self::$defaultSeparator;
        }
        $this->separator = $separator;
    }
# -- Constructor

# Members of Dotenv\Repository\Adapter\ReaderInterface
    /**
     * Create a new instance of the adapter.
     *
     * @return \PhpOption\Option<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create(): Some {
        /** @var \PhpOption\Option<AdapterInterface> */
        return Some::create(new self(self::$defaultSeparator));
    }

## -- Members of Dotenv\Repository\Adapter\ReaderInterface

# Members of Dotenv\Repository\Adapter\WriterInterface
    /**
     * Read a variable from array, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return \PhpOption\Option<string>
     */
    public function read(string $name) {
        $parts = explode($this->separator, $name);

        if (empty($parts)) {
            return None::create();
        }

        $value = $this->variables;
        foreach ($parts as $key) {
            if (!isset($value[$key])) {
                return None::create();
            }

            $value = $value[$key];
        }

        return Option::fromValue($value);
    }

    /**
     * Write a value to a multilevel array.
     *
     * @param non-empty-string $name
     * @param string           $value
     *
     * @return bool
     */
    public function write(string $name, string $value) {
        $parts = explode($this->separator, $name);
        $count = count($parts);

        $depth = 0;
        $array = &$this->variables;
        foreach ($parts as $key) {
            $depth++;
            if ($depth === $count) {
                if (isset($array[$key]) &&
                    is_array($array[$key])) {

                    $array[$key][] = $value;
                } else {
                    $array[$key] = $value;
                }
            } elseif (!isset($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        return true;
    }

    /**
     * Delete a value or branch from a multilevel array.
     *
     * @param non-empty-string $name
     *
     * @return bool
     */
    public function delete(string $name) {
        $parts = explode($this->separator, $name);
        $count = count($parts);

        $depth = 0;
        $array = &$this->variables;
        foreach ($parts as $key) {
            $depth++;
            if (!isset($array[$key])) {
                break;
            } elseif ($depth === $count) {
                unset($array[$key]);
            }

            $array = &$array[$key];
        }

        return true;
    }
# -- Members of Dotenv\Repository\Adapter\WriterInterface

# Members of Dotenv\Repository\Adapter\DecryptableAdapter
    /**
     * Gets de stored values
     *
     * @var array<string, mixed>
     */
    public array $values {
        get => $this->variables;
    }

    public function decrypt(#[SensitiveParameter] KeyProviderInterface $keyProvider): void {
        foreach ($this->variables as $key => $value) {
            if ($key == 'DOTENV_PUBLIC_KEY') {
                continue;
            }
            if (is_string($value) &&
                substr($value, 0, 10) == 'encrypted:') {

                require_once __DIR__ . '/../Decryptor.php';
                $decryptedValue = Decryptor::decrypt($value, $keyProvider);
                $this->write($key, $decryptedValue);
            } elseif (is_array($value)) {
                $this->decryptLevel($keyProvider, [$key]);
            }
        }
    }

    /**
     * Return if the adapter contains encrypted values, and there is a public key
     *
     * @param  ?string              $publicKey (Optional) The public key used for encryption.
     * @return string|false                    The public key if encrypted values are found, otherwise false.
     * @throws RuntimeException                If there isn't public key when encrypted values exist.
     */
    public function isEncrypted(?string $publicKey = null): string|false {
        $hasEncryptedValues = false;
        // Find public key and encrypted values
        foreach ($this->variables as $key => $value) {
            if ($publicKey == null &&
                !empty($publicKey) &&
                $hasEncryptedValues) {

                return $publicKey;
            }
            if ($key == 'DOTENV_PUBLIC_KEY' &&
                is_string($value) &&
                !empty($value)) {

                $publicKey = $value;
            } elseif (!$hasEncryptedValues &&
                      is_string($value) &&
                      substr($value, 0, 10) == 'encrypted:') {
                $hasEncryptedValues = true;
            } elseif (!$hasEncryptedValues &&
                      is_array($value)) {
                $hasEncryptedValues = $this->isEncryptedLevel([$key]);
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

# Methods
    /**
     * Return a key name from a multilevel xpath
     *
     * @param  array $names
     * @return string
     */
    public function getKey(array $names): string {
        return implode($this->separator, $names);
    }

    /**
     * Decrypt all encrypted values of the selected an children levels.
     *
     * @param  KeyProviderInterface $keyProvider Keys used for decryption.
     * @param  array<string>        $xPath       The path to the values to decrypt within the ArrayMultiAdapter instance.
     * @return void
     */
    protected function decryptLevel(#[SensitiveParameter] KeyProviderInterface $keyProvider, array $xPath = []): void {
        $values     = $this->variables;
        foreach ($xPath as $part) {
            $values = $values[$part];
        }
        foreach ($values as $key => $value) {
            if (is_string($value) &&
                substr($value, 0, 10) == 'encrypted:') {

                require_once __DIR__ . '/../Decryptor.php';
                $decryptedValue = Decryptor::decrypt($value, $keyProvider);
                $name = $this->getKey(array_merge($xPath, [$key]));
                $this->write($name, $decryptedValue);
            } elseif (is_array($value)) {
                $this->decryptLevel($keyProvider, array_merge($xPath, [$key]));
            }
        }
    }

    /**
     * Return if the adapter level or children contains encrypted values.
     *
     * @param  array<string> $xPath The path to the values to decrypt within the ArrayMultiAdapter instance.
     * @return bool                 If encrypted values are found, otherwise false.
     */
    public function isEncryptedLevel(array $xPath = []): bool {
        $hasEncryptedValues = false;
        $values     = $this->variables;
        foreach ($xPath as $part) {
            $values = $values[$part];
        }
        // Find encrypted values
        foreach ($values as $key => $value) {
            if ($hasEncryptedValues) {
                break;
            }
            if (is_string($value) &&
                      substr($value, 0, 10) == 'encrypted:') {
                $hasEncryptedValues = true;
                break;
            } elseif (is_array($value)) {
                $hasEncryptedValues = $this->isEncryptedLevel(array_merge($xPath, [$key]));
            }
        }
        return $hasEncryptedValues;
    }
# -- Methods
}
