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
use Rodas\Dotenvx\Adapter\ArrayAdapter;

require_once 'Rodas/Dotenvx/Adapter/ArrayAdapter.php';

/**
 * Test class for ArrayAdapter
 *
 * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
 */
class ArrayAdapterTest extends TestCase {

    public function testWrite() {
        $adapter = new ArrayAdapter();
        $adapter->write('APP_DB_HOST', 'localhost');
        $values = $adapter->values;

        $this->assertTrue(isset($values['APP_DB_HOST']));
        $this->assertEquals('localhost', $values['APP_DB_HOST']);
    }

    public function testWriteRead() {
        $adapter = new ArrayAdapter();
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

    public function testCreate() {
        $adapter = ArrayAdapter::create()->get();

        $this->assertTrue($adapter instanceof ArrayAdapter);
    }
}
