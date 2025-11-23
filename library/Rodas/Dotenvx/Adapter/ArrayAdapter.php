<?php
/**
 * This file is part of the Rodas\Doventx library
 *
 * Based on Dotenv\Repository\Adapter\ArrayAdapter.php
 * vlucas/phpdotenv from Vance Lucas and Graham Campbell.
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
use PhpOption\Option;
use PhpOption\Some;
use Rodas\Dotenvx\Decryptor;
use Rodas\Dotenvx\Provider\KeyProviderInterface;
use SensitiveParameter;

require_once 'Dotenv/Repository/Adapter/AdapterInterface.php';
require_once __DIR__ . '/DecryptableAdapter.php';

class ArrayAdapter implements AdapterInterface, DecryptableAdapter {
# Fields
    /**
     * The variables and their values.
     *
     * @var array<string, string>
     */
    private array $variables;
# -- Fields

# Constructor
    /**
     * Create a new array adapter instance.
     */
    public function __construct() {
        $this->variables = [];
    }
# -- Constructor

# Members of Dotenv\Repository\Adapter\ReaderInterface
    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \PhpOption\Option<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create(): Some {
        require_once 'PhpOption/Some.php';
        /** @var \PhpOption\Option<AdapterInterface> */
        return Some::create(new self());
    }
## -- Members of Dotenv\Repository\Adapter\ReaderInterface

# Members of Dotenv\Repository\Adapter\WriterInterface
    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return \PhpOption\Option<string>
     */
    public function read(string $name) {
        require_once 'PhpOption/Option.php';
        return Option::fromArraysValue($this->variables, $name);
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @param string           $value
     *
     * @return bool
     */
    public function write(string $name, string $value) {
        $this->variables[$name] = $value;

        return true;
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     *
     * @return bool
     */
    public function delete(string $name) {
        unset($this->variables[$name]);

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

    /**
     * Decrypt all encrypted values.
     *
     * @param  KeyProviderInterface $keyProvider Keys used for decryption.
     * @return void
     */
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
            if ($publicKey != null &&
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

# -- Members of Dotenv\Repository\Adapter\DecryptableAdapter
}
