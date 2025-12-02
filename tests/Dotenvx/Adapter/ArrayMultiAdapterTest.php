<?php
/**
 * This file is part of the Rodas\Dotenvx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rodas\Dotenvx
 * @subpackage Test
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Test\Dotenvx\Adapter;

use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Rodas\Dotenvx\Adapter\ArrayMultiAdapter;
use Rodas\Dotenvx\Dotenvx;
use Rodas\Dotenvx\Provider\StaticKeyProvider;
use Rodas\Test\Dotenvx\FakeDecrypt;

use function file_exists;
use function in_array;
use function is_string;

require_once 'Dotenv/Repository/RepositoryBuilder.php';
require_once 'Rodas/Dotenvx/Dotenvx.php';
require_once 'Rodas/Dotenvx/Adapter/ArrayMultiAdapter.php';
require_once 'Rodas/Dotenvx/Provider/StaticKeyProvider.php';
require_once __DIR__ . '/../FakeDecrypt.php';

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
        self::loadFile($this);
    }

    /**
     * Test ArrayMultiAdapter::decrypt
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->decrypt
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->isEncrypted
     * @covers Rodas\Dotenvx\Provider\StaticKeyProvider
     */
    public function testDecryptFile() {
        // Load data
        $arrayAdapter       = self::loadFile($this);

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
                $privateData        = Dotenvx::createArrayBacked(self::PATH, $privateEnvKeyFile)->load();
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
                $isEncrypted        = $arrayAdapter->isEncrypted();
                $this->assertFalse($isEncrypted);
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

    /**
     * Test ArrayMultiAdapter::replaceEncryptedValues
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->getEncryptedValues
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->isEncrypted
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter->replaceEncryptedValues
     */
    public function testReplaceEncryptedValues() {
        // Load data
        $arrayAdapter       = self::loadFile($this);

        // Find public key
        $publicKey          = $arrayAdapter->isEncrypted();
        $arrayAdapter->delete('DOTENV_PUBLIC_KEY');
        $hasPublicKey       = is_string($publicKey);
        $this->assertTrue($hasPublicKey);
        $this->assertEquals('Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA=', $publicKey);
        if ($hasPublicKey) {

            // Decrypt data
            $encryptedValues    = $arrayAdapter->getEncryptedValues();
            $decryptedValues    = FakeDecrypt::decrypt($publicKey, $encryptedValues);
            $isEncrypted        = $arrayAdapter->replaceEncryptedValues($decryptedValues);
            $this->assertFalse($isEncrypted);
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

    public static function loadFile(Assert $assert): ArrayMultiAdapter {
        $envFile            = 'multilevel.env';
        $envFileExists      = file_exists(self::PATH . '/' . $envFile);
        $arrayAdapter       = new ArrayMultiAdapter('.');
        $assert->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenvx::create($repository, self::PATH, $envFile)->load();
            $options            = $arrayAdapter->values;
            $assert->assertEquals('Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA='                                          , $options['DOTENV_PUBLIC_KEY']);
            $assert->assertEquals('pdo_mysql'                                                                             , $options['DB']['DRIVER']);
            $assert->assertEquals('encrypted:kpOFCd76bsEMvgk7iJ1a7oHbQdGITAMAtUppEIBgRmUjinhWoxaKJD9Xz1SqKEwSGAlnuWhXksv1', $options['DB']['HOST']);        // localhost
            $assert->assertEquals('encrypted:k4hknNltlTjry3LFPsM3dtHkQdfJhWRRCK+X21JE6xAjg0xI3bT3rSXfJ9rdesIXWxYFzw=='    , $options['DB']['PORT']);        // 3306
            $assert->assertEquals('encrypted:XZA6xt1uXF1OdDrROuvC5+zVD/3OwXaj9dgPGdkF0QFUaNfCFTcCsmJl7V5e9I7w39egprAOXJg=', $options['DB']['USER']);        // username
            $assert->assertEquals('encrypted:iRJUQ3XaVQnhsUfea2i1NgZWb593oWXhjksHDeC2yzZFPKTsU7UC+D/vxDksSkDFff12oAqzVXk=', $options['DB']['PASSWORD']);    // 'pa$$w0rd'
            $assert->assertEquals('utf8mb4'                                                                               , $options['DB']['CHARSET']);
            $assert->assertTrue(in_array('primary', $options['DB']));
        }

        return $arrayAdapter;
    }
}
