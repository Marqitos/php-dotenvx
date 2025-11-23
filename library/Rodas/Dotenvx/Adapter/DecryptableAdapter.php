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

use Rodas\Dotenvx\Provider\KeyProviderInterface;
use SensitiveParameter;

require_once 'Rodas/Dotenvx/Provider/KeyProviderInterface.php';

/**
 * Represents an adapter that can contain encrypted values
 */
interface DecryptableAdapter {
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
     * Return if contains encrypted values, and there is a public key
     *
     * @param  ?string              $publicKey (Optional) The public key used for encryption.
     * @return string|false                    The public key if encrypted values are found, otherwise false.
     * @throws RuntimeException                If there isn't public key when encrypted values exist.
     */
    public function isEncrypted(?string $publicKey = null): string|false;
}
