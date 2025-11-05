<?php
/**
 * This file is part of the Rodas\Doventx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Test\Dotenvx\Adapter;

use PHPUnit\Framework\TestCase;
use Rodas\Dotenvx\Adapter\ArrayMultiAdapter;

/**
 * Test class for ArrayMultiAdapter
 *
 * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
 */
class ArrayMultiAdapterTest extends TestCase {

    public function testWrite() {
        $adapter = new ArrayMultiAdapter('.');
        $adapter->write('APP.DB.HOST', 'localhost');
        $values = $adapter->values;

        $this->assertTrue(isset($values['APP']));
        $this->assertTrue(isset($values['APP']['DB']));
        $this->assertTrue(isset($values['APP']['DB']['HOST']));
    }

    public function testWriteRead() {
        $adapter = new ArrayMultiAdapter('.');
        $adapter->write('APP.DB.HOST', 'localhost');
        $host       = $adapter->read('APP.DB.HOST');
        $isEmpty    = $host->isEmpty();
        $values     = $adapter->values;
        
        $this->assertFalse($isEmpty);
        if (!$isEmpty) {
            $this->assertEquals('localhost', $host->get());
        }
        $this->assertTrue(isset($values['APP']));
        $this->assertTrue(isset($values['APP']['DB']));
        $this->assertTrue(isset($values['APP']['DB']['HOST']));
    }

    public function testCreate() {
        ArrayMultiAdapter::$defaultSeparator = '-';
        $adapter = ArrayMultiAdapter::create()->get();
        ArrayMultiAdapter::$defaultSeparator = '.';
        $separator = $adapter->separator;

        $this->assertEquals($separator, '-');
    }
}
