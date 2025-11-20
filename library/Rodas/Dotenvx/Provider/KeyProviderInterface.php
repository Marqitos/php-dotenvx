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

namespace Rodas\Dotenvx\Provider;

/**
 * Store the public and private keys necessary for decrypting values.
 */
interface KeyProviderInterface {
    /**
     * Get the public key
     *
     * @var string
     */
    public string $publicKey { get; }
    /**
     * Get the private key
     *
     * @var string
     */
    public string $privateKey { get; }
}
