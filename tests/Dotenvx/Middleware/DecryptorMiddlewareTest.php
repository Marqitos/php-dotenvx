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

namespace Rodas\Test\Dotenvx\Middleware;

use Dotenv\Exception\InvalidPathException;
use Dotenv\Loader\Loader;
use Dotenv\Parser\Parser;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Store\StoreBuilder;
use Rodas\Dotenvx\Dotenvx;
use Rodas\Dotenvx\Adapter\ArrayAdapter;
use Rodas\Dotenvx\Adapter\ArrayMultiAdapter;
use Rodas\Dotenvx\Middleware\DecryptorMiddleware;
use Rodas\Test\Dotenvx\Data;
use Rodas\Test\Dotenvx\FakeDecrypt;

class DecryptorMiddlewareTest extends Data {
    // TODO: Partial of data
    const DEFAULT = [
        'DOTENV_PUBLIC_KEY' => 'Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA=',
        'DB_DRIVER' => 'pdo_mysql',
        'DB_HOST' => 'localhost',
        'DB_PORT' => '3306',
        'DB_USER' => 'username',
        'DB_PASSWORD' => 'pa$$w0rd',
        'DB_CHARSET' => 'utf8mb4',
        'SPACED' => 'with spaces',
        'NULL' => '',
    ];

    /**
     * Test Dotenvx::load with DecryptorMiddleware and ArrayAdapter
     *
     * @covers Rodas\Dotenvx\Dotenvx::load
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
     * @covers Rodas\Dotenvx\Middleware\DecryptorMiddleware
     */
    public function testLoadEncrypted() {
        $envFileExists      = file_exists($this->path . '/.env');
        $arrayAdapter       = new ArrayAdapter();
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            $middleware         = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
            Dotenvx::create($repository, $this->path)
                ->addMiddleware($middleware)
                ->load();

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

    /**
     * Test Dotenvx::load with DecryptorMiddleware and ArrayMultiAdapter
     *
     * @covers Rodas\Dotenvx\Dotenvx::load
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
     * @covers Rodas\Dotenvx\Middleware\DecryptorMiddleware
     */
    public function testLoadEncryptedMulti() {
        $envFile            = 'multilevel.env';
        $envFileExists      = file_exists($this->path . '/' . $envFile);
        $arrayAdapter       = new ArrayMultiAdapter('.');
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            $middleware         = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
            Dotenvx::create($repository, $this->path, $envFile)
                ->addMiddleware($middleware)
                ->load();

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

    public function testDotenvThrowsExceptionIfUnableToLoadFile() {
        $dotenv = Dotenvx::createMutable(__DIR__);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $middleware             = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();
    }

    public function testDotenvThrowsExceptionIfUnableToLoadFiles() {
        $dotenv = Dotenvx::createMutable([__DIR__, __DIR__.'/foo/bar']);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $middleware             = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();
    }

    public function testDotenvThrowsExceptionWhenNoFiles() {
        $dotenv = Dotenvx::createMutable([]);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('At least one environment file path must be provided.');

        $middleware             = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();
    }

    public function testDotenvTriesPathsToLoad() {
        $dotenv = Dotenvx::createMutable([__DIR__, $this->path]);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertCount(9                , $options);
        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvTriesPathsToLoadTwice() {
        $dotenv = Dotenvx::createMutable([__DIR__, $this->path]);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();
        $this->assertCount(9, $options);

        $dotenv = Dotenvx::createImmutable([__DIR__, $this->path]);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();
        $this->assertCount(0, $options);
    }

    public function testDotenvTriesPathsToSafeLoad() {
        $dotenv = Dotenvx::createMutable([__DIR__, $this->path]);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->safeLoad();
        $this->assertCount(9                , $options);
        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable($this->path);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertSame(self::DEFAULT     , $options);
        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithShortCircuitMode() {
        $dotenv = Dotenvx::createMutable($this->path, ['.env', 'example.env']);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertSame(self::DEFAULT     , $options);
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithoutShortCircuitMode() {
        $dotenv = Dotenvx::createMutable($this->path, ['.env', 'example.env'], false);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertSame(
            self::DEFAULT + ['EG' => 'example'],
            $options
        );
    }

    public function testDotenvLoadsEnvGlobals() {
        $dotenv = Dotenvx::createMutable($this->path);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();

        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals() {
        $dotenv = Dotenvx::createMutable($this->path);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();

        $this->assertEquals('pdo_mysql'     , $_ENV['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_ENV['DB_HOST']);
        $this->assertEquals('3306'          , $_ENV['DB_PORT']);
        $this->assertEquals('username'      , $_ENV['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_ENV['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_ENV['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_ENV['SPACED']);
        $this->assertEmpty($_ENV['NULL']);
    }

    public function testDotenvNullFileArgumentUsesDefault() {
        $dotenv = Dotenvx::createMutable($this->path, null);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();

        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDirectConstructor() {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();
        $store = StoreBuilder::createWithDefaultName()->addPath($this->path)->make();

        $dotenv = new Dotenvx($store, new Parser(), new Loader(), $repository);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertSame(self::DEFAULT, $options);
    }

}
