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

namespace Rodas\Dotenvx\Provider;

require_once __DIR__ . '/KeyProviderInterface.php';

/**
 * Store keys provides on construction
 */
class StaticKeyProvider implements KeyProviderInterface {

    /**
     * Create a new StaticKeyProvider instance.
     *
     * @param string $publicKey  Encryption public key
     * @param string $privateKey Decryption private key
     */
    public function __construct(string $publicKey,#[SensitiveParameter] string $privateKey) {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

# KeyProviderInterface Members
    /**
     * Get the public key
     *
     * @var string
     */
    public private(set) string $publicKey {
        set (string $value) {
            $this->publicKey = $value;
        }
    }
    /**
     * Get the private key
     *
     * @var string
     */
    public private(set) string $privateKey {
        set (string $value) {
            $this->privateKey = $value;
        }
    }
# -- KeyProviderInterface Members
}
