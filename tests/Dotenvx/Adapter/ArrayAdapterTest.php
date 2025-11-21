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

/**
 * Test class for ArrayAdapter
 *
 * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
 */
class ArrayAdapterTest extends TestCase {

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
        $path               = __DIR__ . '/../../fixtures/env';
        $envFile            = '.env';
        $envFileExists      = file_exists($path . '/' . $envFile);
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $arrayAdapter       = new ArrayAdapter();
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenv::create($repository, $path, $envFile)->load();
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
}
