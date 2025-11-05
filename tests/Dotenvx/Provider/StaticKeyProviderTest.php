<?php
/**
 * This file is part of the Rodas\Doventx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rodas\Doventx
 * @subpackage Test
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Test\Dotenvx\Adapter;

use PHPUnit\Framework\TestCase;
use Rodas\Dotenvx\Provider\StaticKeyProvider;

/**
 * Test class for StaticKeyProvider
 *
 * @covers Rodas\Dotenvx\Provider\StaticKeyProvider
 */
class StaticKeyProviderTest extends TestCase {

    /**
     * Test StaticKeyProvider constructor and properties
     *
     * @covers Rodas\Dotenvx\Provider\StaticKeyProvider::__construct
     * @covers Rodas\Dotenvx\Provider\StaticKeyProvider->publicKey
     * @covers Rodas\Dotenvx\Provider\StaticKeyProvider->privateKey
     */
    public function testStaticKeyProvider() {
        $publicKey          = 'publicKeyExample12345';
        $privateKey         = 'privateKeyExample12345';
        $provider           = new StaticKeyProvider($publicKey, $privateKey);
        $propertyPublicKey  = $provider->publicKey;
        $propertyPrivateKey = $provider->privateKey;

        $this->assertEquals($publicKey, $propertyPublicKey);
        $this->assertEquals($privateKey, $propertyPrivateKey);
    }
}
