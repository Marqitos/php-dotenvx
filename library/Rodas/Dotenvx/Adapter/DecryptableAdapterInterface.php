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

namespace Rodas\Dotenvx\Adapter;

use Rodas\Dotenvx\Provider\KeyProviderInterface;
use SensitiveParameter;

require_once 'Rodas/Dotenvx/Provider/KeyProviderInterface.php';

/**
 * Represents an adapter that can contain encrypted values
 */
interface DecryptableAdapterInterface {
    /**
     * Gets de stored values
     *
     * @var array<string, mixed>
     */
    public array $values { get; }

    /**
     * Decrypt all encrypted values.
     *
     * @param  KeyProviderInterface $keyProvider Keys used for decryption.
     * @return void
     */
    public function decrypt(#[SensitiveParameter] KeyProviderInterface $keyProvider): void;

    /**
     * Return all encrypted values as base64 encoded strings
     *
     * @return array<string> All encrypted values as base64 encoded strings
     */
    public function getEncryptedValues(): array;

    /**
     * Return if contains encrypted values, and there is a public key
     *
     * @param  ?string              $publicKey (Optional) The public key used for encryption.
     * @return string|false                    The public key if encrypted values are found, otherwise false.
     * @throws RuntimeException                If there isn't public key when encrypted values exist.
     */
    public function isEncrypted(?string $publicKey = null): string|false;

    /**
     * Replace encrypted values with decrypted values
     *
     * @param  array<string, mixed> $values Decrypted values, encrypted values as keys.
     * @return bool                         Still contains encrypted values after replacement.
     */
    public function replaceEncryptedValues(#[SensitiveParameter] array $decryptedValues): bool;
}
