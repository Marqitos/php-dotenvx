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

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\TestCase;
use Rodas\Dotenvx\Adapter\ArrayAdapter;
use Rodas\Dotenvx\Provider\StaticKeyProvider;

use function file_exists;
use function is_string;

require_once 'Dotenv/Dotenv.php';
require_once 'Dotenv/Repository/RepositoryBuilder.php';
require_once 'Rodas/Dotenvx/Adapter/ArrayAdapter.php';
require_once 'Rodas/Dotenvx/Provider/StaticKeyProvider.php';

/**
 * Test class for ArrayAdapter
 *
 * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
 */
class ArrayAdapterTest extends TestCase {

    const PATH = __DIR__ . '/../../fixtures/env';

    /**
     * Test ArrayAdapter->write
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter->values
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter->write
     */
    public function testWrite() {
        $adapter = new ArrayAdapter();
        $adapter->write('APP_DB_HOST', 'localhost');
        $values = $adapter->values;

        $this->assertTrue(isset($values['APP_DB_HOST']));
        $this->assertEquals('localhost', $values['APP_DB_HOST']);
    }

    /**
     * Test ArrayAdapter->read && ArrayAdapter->write
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter->read
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter->values
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter->write
     */
    public function testWriteRead() {
        $adapter    = new ArrayAdapter();
        $adapter->write('APP_DB_HOST', 'localhost');
        $host       = $adapter->read('APP_DB_HOST');
        $isEmpty    = $host->isEmpty();
        $values     = $adapter->values;

        $this->assertFalse($isEmpty);
        if (!$isEmpty) {
            $this->assertEquals('localhost', $host->get());
        }
        $this->assertTrue(isset($values['APP_DB_HOST']));
        $this->assertEquals('localhost', $values['APP_DB_HOST']);

        // Modification test
        $values                 = $adapter->values;
        $values['APP_DB_HOST']  = 'modified';
        $app                    = $adapter->read('APP_DB_HOST');
        $this->assertNotEquals('modified', $app->isEmpty());
    }

    /**
     * Test ArrayAdapter::create
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter::create
     */
    public function testCreate() {
        $adapter = ArrayAdapter::create()->get();

        $this->assertTrue($adapter instanceof ArrayAdapter);
    }

    /**
     * Test Dovent with ArrayAdapter
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter::__construct
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter->values
     */
    public function testReadFile() {
        $envFile            = '.env';
        $envFileExists      = file_exists(self::PATH . '/' . $envFile);
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $arrayAdapter       = new ArrayAdapter();
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenv::create($repository, self::PATH, $envFile)->load();
            $options            = $arrayAdapter->values;
            $this->assertEquals('Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA='                                          , $options['DOTENV_PUBLIC_KEY']);
            $this->assertEquals('pdo_mysql'                                                                             , $options['DB_DRIVER']);
            $this->assertEquals('encrypted:kpOFCd76bsEMvgk7iJ1a7oHbQdGITAMAtUppEIBgRmUjinhWoxaKJD9Xz1SqKEwSGAlnuWhXksv1', $options['DB_HOST']);        // localhost
            $this->assertEquals('encrypted:k4hknNltlTjry3LFPsM3dtHkQdfJhWRRCK+X21JE6xAjg0xI3bT3rSXfJ9rdesIXWxYFzw=='    , $options['DB_PORT']);        // 3306
            $this->assertEquals('encrypted:XZA6xt1uXF1OdDrROuvC5+zVD/3OwXaj9dgPGdkF0QFUaNfCFTcCsmJl7V5e9I7w39egprAOXJg=', $options['DB_USER']);        // username
            $this->assertEquals('encrypted:iRJUQ3XaVQnhsUfea2i1NgZWb593oWXhjksHDeC2yzZFPKTsU7UC+D/vxDksSkDFff12oAqzVXk=', $options['DB_PASSWORD']);    // 'pa$$w0rd'
            $this->assertEquals('utf8mb4'                                                                               , $options['DB_CHARSET']);
        }
    }

    /**
     * Test ArrayAdapter::decrypt
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter->decrypt
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter->isEncrypted
     * @covers Rodas\Dotenvx\Provider\StaticKeyProvider
     */
    public function testDecryptFile() {
        // Find private key
        $privateEnvKeyFile  = '.env.key';
        $privateKey         = false;
        $privateFileExists  = file_exists(self::PATH . '/' . $privateEnvKeyFile);
        $this->assertTrue($privateFileExists);
        if ($privateFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter(ArrayAdapter::class)
                ->make();
            $dotenv             = Dotenv::create($repository, self::PATH, $privateEnvKeyFile);
            $privateData        = $dotenv->load();
            $containsPrivateKey = isset($privateData['DOTENV_PRIVATE_KEY']);
            $this->assertTrue($containsPrivateKey);
            if ($containsPrivateKey) {
                $privateKey     = $privateData['DOTENV_PRIVATE_KEY'];
            }
            unset($repository, $dotenv, $privateData);
            $this->assertEquals('/llTiaDfwfYIuVaRI1Ah9T3mWgy2FJVuyRUV0CvPVk8=', $privateKey);
        }

        // Load data
        $envFile            = '.env';
        $envFileExists      = file_exists(self::PATH . '/' . $envFile);
        $arrayAdapter       = new ArrayAdapter();
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenv::create($repository, self::PATH, $envFile)->load();
            $this->assertEquals('pdo_mysql'                                                                             , $arrayAdapter->values['DB_DRIVER']);
            $this->assertEquals('encrypted:kpOFCd76bsEMvgk7iJ1a7oHbQdGITAMAtUppEIBgRmUjinhWoxaKJD9Xz1SqKEwSGAlnuWhXksv1', $arrayAdapter->values['DB_HOST']);        // localhost
            $this->assertEquals('encrypted:k4hknNltlTjry3LFPsM3dtHkQdfJhWRRCK+X21JE6xAjg0xI3bT3rSXfJ9rdesIXWxYFzw=='    , $arrayAdapter->values['DB_PORT']);        // 3306
            $this->assertEquals('encrypted:XZA6xt1uXF1OdDrROuvC5+zVD/3OwXaj9dgPGdkF0QFUaNfCFTcCsmJl7V5e9I7w39egprAOXJg=', $arrayAdapter->values['DB_USER']);        // username
            $this->assertEquals('encrypted:iRJUQ3XaVQnhsUfea2i1NgZWb593oWXhjksHDeC2yzZFPKTsU7UC+D/vxDksSkDFff12oAqzVXk=', $arrayAdapter->values['DB_PASSWORD']);    // 'pa$$w0rd'
            $this->assertEquals('utf8mb4'                                                                               , $arrayAdapter->values['DB_CHARSET']);
        }

        // Find public key
        $publicKey          = $arrayAdapter->isEncrypted();
        $arrayAdapter->delete('DOTENV_PUBLIC_KEY');
        $this->assertTrue(is_string($publicKey));
        $this->assertEquals('Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA=', $publicKey);
        if (is_string($publicKey)) {
            $this->assertTrue(is_string($privateKey));
            // Decrypt data
            if (is_string($privateKey)) {
                $staticKeyProvider  = new StaticKeyProvider($publicKey, $privateKey);
                $arrayAdapter->decrypt($staticKeyProvider);
            } else {
                throw new Exception('Private key not found');
            }
        }

        // Validate values
        $options            = $arrayAdapter->values;
        $this->assertEquals('pdo_mysql' , $options['DB_DRIVER']);
        $this->assertEquals('localhost' , $options['DB_HOST']);
        $this->assertEquals('3306'      , $options['DB_PORT']);
        $this->assertEquals('username'  , $options['DB_USER']);
        $this->assertEquals('pa$$w0rd'  , $options['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'   , $options['DB_CHARSET']);
    }
}
