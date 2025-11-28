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

namespace Rodas\Test\Dotenvx;

use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\TestCase;
use Rodas\Dotenvx\Dotenvx;
use Rodas\Dotenvx\Adapter\ArrayAdapter;
use Rodas\Test\Dotenvx\FakeDecrypt;


/**
 * Test class for Dotenvx
 *
 * @covers Rodas\Dotenvx\Dotenvx
 */
class DotenvxTest extends TestCase {

    const PATH = __DIR__ . '/../fixtures/env';

    public function testLoadEncrypted() {
        $envFileExists      = file_exists(self::PATH . '/.env');
        $arrayAdapter       = new ArrayAdapter();
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenvx::create($repository, self::PATH)->loadEncrypted([FakeDecrypt::class, 'decrypt']);

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
}
