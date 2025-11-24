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
use Rodas\Dotenvx\Adapter\ArrayMultiAdapter;
use Rodas\Dotenvx\Provider\StaticKeyProvider;

use function file_exists;
use function in_array;
use function is_string;

require_once 'Dotenv/Dotenv.php';
require_once 'Dotenv/Repository/RepositoryBuilder.php';
require_once 'Rodas/Dotenvx/Adapter/ArrayAdapter.php';
require_once 'Rodas/Dotenvx/Adapter/ArrayMultiAdapter.php';
require_once 'Rodas/Dotenvx/Provider/StaticKeyProvider.php';

/**
 * Test class for ArrayMultiAdapter
 *
 * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
 */
class ArrayMultiAdapterTest extends TestCase {

    const PATH = __DIR__ . '/../../fixtures/env';

    /**
     * Test ArrayMultiAdapter->write
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->values
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->write
     */
    public function testWrite() {
        $adapter = new ArrayMultiAdapter('.');
        $adapter->write('APP.DB.HOST', 'localhost');
        $values = $adapter->values;

        $this->assertTrue(isset($values['APP']));
        $this->assertTrue(isset($values['APP']['DB']));
        $this->assertTrue(isset($values['APP']['DB']['HOST']));
        $this->assertEquals('localhost', $values['APP']['DB']['HOST']);
    }

    /**
     * Test ArrayMultiAdapter->read && ArrayMultiAdapter->write
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->read
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->values
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->write
     */
    public function testWriteRead() {
        $adapter    = new ArrayMultiAdapter('.');
        $adapter->write('APP.DB.HOST', 'localhost');
        $host       = $adapter->read('APP.DB.HOST');
        $isEmpty    = $host->isEmpty();

        $this->assertFalse($isEmpty);
        if (!$isEmpty) {
            $this->assertEquals('localhost', $host->get());
        }
        $values     = $adapter->values;
        $this->assertTrue(isset($values['APP']));
        $this->assertTrue(isset($values['APP']['DB']));
        $this->assertTrue(isset($values['APP']['DB']['HOST']));
        $this->assertEquals('localhost', $values['APP']['DB']['HOST']);

        // Modification test
        $values         = $adapter->values;
        $values['APP']  = 'modified';
        $app            = $adapter->read('APP');
        $this->assertNotEquals('modified', $app->isEmpty());
    }

    /**
     * Test ArrayMultiAdapter::create
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter::create
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter::$defaultSeparator
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->separator
     */
    public function testCreate() {
        ArrayMultiAdapter::$defaultSeparator = '-';
        $adapter = ArrayMultiAdapter::create()->get();
        ArrayMultiAdapter::$defaultSeparator = '.';
        $separator = $adapter->separator;

        $this->assertEquals('-', $separator);
    }

    /**
     * Test ArrayMultiAdapter->getKey
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter::$defaultSeparator
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->getKey
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->read
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->values
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->write
     */
    public function testGetKey() {
        $adapter    = new ArrayMultiAdapter(ArrayMultiAdapter::$defaultSeparator);
        $key        = $adapter->getKey(['APP', 'DB', 'HOST']);
        $adapter->write($key, 'localhost');
        $host       = $adapter->read($key);
        $isEmpty    = $host->isEmpty();

        $this->assertFalse($isEmpty);
        if (!$isEmpty) {
            $this->assertEquals('localhost', $host->get());
        }
        $values     = $adapter->values;
        $this->assertTrue(isset($values['APP']));
        $this->assertTrue(isset($values['APP']['DB']));
        $this->assertTrue(isset($values['APP']['DB']['HOST']));
        $this->assertEquals('localhost', $values['APP']['DB']['HOST']);
    }

    /**
     * Test Dovent with ArrayMultiAdapter
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter::__construct
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->values
     */
    public function testReadFile() {
        $envFile            = 'multilevel.env';
        $envFileExists      = file_exists(self::PATH . '/' . $envFile);
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $arrayAdapter       = new ArrayMultiAdapter('.');
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenv::create($repository, self::PATH, $envFile)->load();
            $options            = $arrayAdapter->values;
            $this->assertEquals('Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA='                                          , $options['DOTENV_PUBLIC_KEY']);
            $this->assertEquals('pdo_mysql'                                                                             , $options['DB']['DRIVER']);
            $this->assertEquals('encrypted:kpOFCd76bsEMvgk7iJ1a7oHbQdGITAMAtUppEIBgRmUjinhWoxaKJD9Xz1SqKEwSGAlnuWhXksv1', $options['DB']['HOST']);        // localhost
            $this->assertEquals('encrypted:k4hknNltlTjry3LFPsM3dtHkQdfJhWRRCK+X21JE6xAjg0xI3bT3rSXfJ9rdesIXWxYFzw=='    , $options['DB']['PORT']);        // 3306
            $this->assertEquals('encrypted:XZA6xt1uXF1OdDrROuvC5+zVD/3OwXaj9dgPGdkF0QFUaNfCFTcCsmJl7V5e9I7w39egprAOXJg=', $options['DB']['USER']);        // username
            $this->assertEquals('encrypted:iRJUQ3XaVQnhsUfea2i1NgZWb593oWXhjksHDeC2yzZFPKTsU7UC+D/vxDksSkDFff12oAqzVXk=', $options['DB']['PASSWORD']);    // 'pa$$w0rd'
            $this->assertEquals('utf8mb4'                                                                               , $options['DB']['CHARSET']);
            $this->assertTrue(in_array('primary', $options['DB']));
        }
    }

    /**
     * Test ArrayMultiAdapter::decrypt
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->decrypt
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->isEncrypted
     * @covers Rodas\Dotenvx\Provider\StaticKeyProvider
     */
    public function testDecryptFile() {
        // Load data
        $envFile            = 'multilevel.env';
        $publicKey          = false;
        $envFileExists      = file_exists(self::PATH . '/' . $envFile);
        $arrayAdapter       = new ArrayMultiAdapter('.');
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenv::create($repository, self::PATH, $envFile)->load();
            $this->assertEquals('pdo_mysql'                                                                             , $arrayAdapter->values['DB']['DRIVER']);
            $this->assertEquals('encrypted:kpOFCd76bsEMvgk7iJ1a7oHbQdGITAMAtUppEIBgRmUjinhWoxaKJD9Xz1SqKEwSGAlnuWhXksv1', $arrayAdapter->values['DB']['HOST']);        // localhost
            $this->assertEquals('encrypted:k4hknNltlTjry3LFPsM3dtHkQdfJhWRRCK+X21JE6xAjg0xI3bT3rSXfJ9rdesIXWxYFzw=='    , $arrayAdapter->values['DB']['PORT']);        // 3306
            $this->assertEquals('encrypted:XZA6xt1uXF1OdDrROuvC5+zVD/3OwXaj9dgPGdkF0QFUaNfCFTcCsmJl7V5e9I7w39egprAOXJg=', $arrayAdapter->values['DB']['USER']);        // username
            $this->assertEquals('encrypted:iRJUQ3XaVQnhsUfea2i1NgZWb593oWXhjksHDeC2yzZFPKTsU7UC+D/vxDksSkDFff12oAqzVXk=', $arrayAdapter->values['DB']['PASSWORD']);    // 'pa$$w0rd'
            $this->assertEquals('utf8mb4'                                                                               , $arrayAdapter->values['DB']['CHARSET']);
            $this->assertTrue(in_array('primary', $arrayAdapter->values['DB']));
        }

        // Find public key
        $publicKey          = $arrayAdapter->isEncrypted();
        $arrayAdapter->delete('DOTENV_PUBLIC_KEY');
        $hasPublicKey       = is_string($publicKey);
        $this->assertTrue($hasPublicKey);
        $this->assertEquals('Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA=', $publicKey);
        if ($hasPublicKey) {

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

            $hasPrivateKey      = is_string($privateKey);
            $this->assertTrue($hasPrivateKey);
            // Decrypt data
            if ($hasPrivateKey) {
                $staticKeyProvider  = new StaticKeyProvider($publicKey, $privateKey);
                $arrayAdapter->decrypt($staticKeyProvider);
            } else {
                throw new Exception('Private key not found');
            }
        }

        // Validate values
        $options            = $arrayAdapter->values;
        $this->assertEquals('pdo_mysql' , $options['DB']['DRIVER']);
        $this->assertEquals('localhost' , $options['DB']['HOST']);
        $this->assertEquals('3306'      , $options['DB']['PORT']);
        $this->assertEquals('username'  , $options['DB']['USER']);
        $this->assertEquals('pa$$w0rd'  , $options['DB']['PASSWORD']);
        $this->assertEquals('utf8mb4'   , $options['DB']['CHARSET']);
        $this->assertTrue(in_array('primary', $options['DB']));
    }
}
