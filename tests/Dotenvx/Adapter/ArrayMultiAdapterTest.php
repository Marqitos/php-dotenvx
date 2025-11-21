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
use Rodas\Dotenvx\Adapter\ArrayMultiAdapter;

use function in_array;

/**
 * Test class for ArrayMultiAdapter
 *
 * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
 */
class ArrayMultiAdapterTest extends TestCase {

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
        $path               = __DIR__ . '/../../fixtures/env';
        $envFile            = 'multilevel.env';
        $envFileExists      = file_exists($path . '/' . $envFile);
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $arrayAdapter       = new ArrayMultiAdapter('.');
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenv::create($repository, $path, $envFile)->load();
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
}
